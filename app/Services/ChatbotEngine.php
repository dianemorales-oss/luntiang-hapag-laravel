<?php

namespace App\Services;

use App\Models\ChatBotState;
use App\Models\LiveChatMessage;

class ChatbotEngine
{
    private ?ChatBotState $stateModel = null;
    private string $chatKey;
    private array $kb;
    private bool $isLoggedIn;
    private array $kbIndexById = [];

    public function __construct(string $chatKey, bool $isLoggedIn = false, $conn = null)
    {
        $this->chatKey = $chatKey;
        $this->isLoggedIn = $isLoggedIn;
        // Support both old signature (PDO, chatKey, isLoggedIn) and new
        if ($conn instanceof \PDO && is_string($chatKey)) {
            // old signature detection handled externally, but keep
        }
        // Support legacy case where first arg was PDO - handled in wrapper
        if (func_num_args() >= 2 && func_get_arg(0) instanceof \PDO) {
            // first arg is PDO, second is chatKey, third is isLoggedIn
            $args = func_get_args();
            $this->chatKey = $args[1] ?? $chatKey;
            $this->isLoggedIn = $args[2] ?? false;
        }

        $kbPath = __DIR__ . '/chatbot-knowledge.php';
        if (file_exists($kbPath)) {
            $this->kb = require $kbPath;
        } else {
            $originalPath = base_path('../original/includes/chatbot-knowledge.php');
            // fallback
            $this->kb = file_exists($originalPath) ? require $originalPath : ['knowledge'=>[], 'services'=>[]];
        }

        foreach ($this->kb['knowledge'] ?? [] as $i => $topic) {
            if (!empty($topic['id'])) {
                $this->kbIndexById[$topic['id']] = $i;
            }
        }
    }

    // Allow old constructor signature: new ChatbotEngine($conn, $chatKey, $isLoggedIn)
    public static function fromLegacy($conn, string $chatKey, bool $isLoggedIn = false): self
    {
        return new self($chatKey, $isLoggedIn);
    }

    public static function greeting(): string
    {
        $greetings = [
            "Hi, I'm the Luntiang H.A.P.A.G. Assistant 🥬 I can answer questions about our products, orders, delivery, and freshness — and I'll help point you to the right form if you need one. What can I help you with today?",
            "Welcome to Luntiang H.A.P.A.G.! 🌱 I'm your AI assistant here to help with orders, product info, delivery questions, and more. What brings you here today?",
            "Hello there! 👋 I'm the Luntiang H.A.P.A.G. Assistant. Need help with an order, have questions about our organic lettuce, or just want to know more about our farm? I'm here to help — just let me know what you need!"
        ];
        return $greetings[array_rand($greetings)];
    }

    public function respond(string $text): array
    {
        $lower = $this->normalize($text);
        $state = $this->getState();

        // Enhanced context: keep last user messages to understand follow-ups without repeating info
        $recentUserMessages = $this->getRecentUserMessages(5);
        $combinedContext = implode(' ', $recentUserMessages) . ' ' . $lower;
        $combinedLower = $this->normalize($combinedContext);

        // If this looks like a follow-up and we have last topic, try to resolve using context first
        if ($this->isFollowUpQuestion($lower, $state['last_topic'])) {
            $contextual = $this->resolveContextualFollowUp($lower, $state['last_topic']);
            if ($contextual !== null) {
                return $contextual;
            }
            // Try ranking with combined context (previous + current) for better accuracy
            $candidatesWithContext = $this->rankedCandidates($combinedLower);
            if (!empty($candidatesWithContext)) {
                return ['replies' => [$this->answerFromCandidate($candidatesWithContext[0], $state['last_topic'])], 'escalate' => false, 'context' => null];
            }
        }

        if ($this->matchesAny($lower, $this->kb['reactivate_keywords'] ?? [])) {
            $this->setState(true, null, null);
            return [
                'replies' => ["I'm back! What can I help you with?"],
                'escalate' => false,
                'context' => null,
            ];
        }

        if (!$state['bot_active']) {
            return ['replies' => [], 'escalate' => false, 'context' => null];
        }

        if ($this->isHelpRequest($lower)) {
            return [
                'replies' => [$this->buildHelpResponse()],
                'escalate' => false,
                'context' => null,
            ];
        }

        if ($state['pending_intent'] === 'damage_timing') {
            return $this->resolveDamageTimingFollowUp($lower, $state['pending_context']);
        }
        if ($state['pending_intent'] === 'disambiguate') {
            return $this->resolveDisambiguation($lower, $state['pending_context'], $state['last_topic']);
        }
        if ($state['pending_intent'] === 'offer') {
            return $this->resolveOffer($lower, $state['pending_context'], $state['last_topic']);
        }
        if ($state['pending_intent'] === 'password_reset_step') {
            return $this->resolvePasswordResetStep($lower, $state['pending_context']);
        }

        if ($this->isPureGreeting($lower)) {
            return ['replies' => [$this->randomFrom($this->kb['greeting_responses'] ?? [], "Hi there! How can I help you today?")], 'escalate' => false, 'context' => null];
        }
        if ($this->isGratitude($lower)) {
            return ['replies' => [$this->randomFrom($this->kb['gratitude_responses'] ?? [], "You're very welcome!")], 'escalate' => false, 'context' => null];
        }

        if ($this->matchesAny($lower, $this->kb['services']['live_agent']['keywords'] ?? [])) {
            $context = $this->getConversationContext();
            $this->setState(false, null, null);
            return [
                'replies' => ["Of course! I'd be happy to help first if I can. If your concern needs additional assistance, I can connect you with one of our live support representatives. I've switched this chat to Agent mode; please share any helpful order details while an agent joins."],
                'escalate' => true,
                'context' => $context,
            ];
        }

        // Live product availability and price answers must come from the database,
        // not from the static knowledge text, so customer answers stay accurate.
        if ($this->matchesAny($lower, ['available', 'availability', 'in stock', 'stock', 'price', 'pricing', 'how much']) &&
            $this->matchesAny($lower, ['lettuce', 'romaine', 'batavia', 'bianca', 'dabi', 'estrosa', 'olmetie', 'red', 'mixed', 'bundle', 'product'])) {
            $products = \App\Models\Product::query()
                ->where('is_active', true)
                ->where('plants_available', '>', 0)
                ->where(function ($query) use ($lower) {
                    foreach (['romaine', 'batavia', 'bianca', 'dabi', 'estrosa', 'olmetie', 'red', 'mixed', 'bundle'] as $term) {
                        if (str_contains($lower, $term)) $query->orWhere('name', 'like', "%{$term}%");
                    }
                })->limit(5)->get();
            if ($products->isNotEmpty()) {
                $lines = $products->map(fn ($product) => "• {$product->name} — ₱" . number_format((float) $product->price, 2) . " ({$product->plants_available} available)")->implode("\n");
                return ['replies' => ["Here is the current availability from our shop:\n{$lines}\n\nYou can view the full product list and add items to your cart from the Shop page."], 'escalate' => false, 'context' => null];
            }
        }

        if ($this->isVagueHelpRequest($lower)) {
            return [
                'replies' => ["Of course — happy to help. Could you tell me a little more about what's going on? For example: is this about an order you placed, a product that arrived with a problem, your account, or something else?"],
                'escalate' => false,
                'context' => null,
            ];
        }

        if ($this->isAmbiguousDamageReport($lower)) {
            $this->setState(true, 'damage_timing', $text);
            return [
                'replies' => ["I can help with that. Was the damage already there when the item arrived, or did it happen after you'd been using it for a while?"],
                'escalate' => false,
                'context' => null,
            ];
        }

        if ($this->matchesAny($lower, ['refund']) && !$this->matchesAny($lower, ['damaged', 'wrong item', 'missing part', 'defective', 'wrong order'])) {
            $this->setState(true, 'damage_timing', $text);
            return [
                'replies' => ["Happy to help sort that out. Did your order arrive wrong, damaged, or missing parts — or did the issue develop after you'd had it a while?"],
                'escalate' => false,
                'context' => null,
            ];
        }

        if ($this->matchesAny($lower, ["don't know which", "dont know which", "which form", "which one should i use", "not sure which"])) {
            return ['replies' => [$this->explainAllServices()], 'escalate' => false, 'context' => null];
        }

        $candidates = $this->rankedCandidates($lower);

        if (empty($candidates)) {
            $contextual = $this->resolveContextualFollowUp($lower, $state['last_topic']);
            if ($contextual !== null) {
                return $contextual;
            }

            return [
                'replies' => [$this->randomFrom($this->kb['fallback_responses'] ?? [], "I'm not sure I understand that yet — could you rephrase, or tell me a bit more about what's going on?")],
                'escalate' => false,
                'context' => null,
            ];
        }

        $top = $candidates[0];
        $second = $candidates[1] ?? null;

        if ($second && $second['score'] > 0 && ($top['score'] - $second['score']) <= 1 && strcasecmp($top['label'], $second['label']) !== 0) {
            $this->setState(true, 'disambiguate', json_encode([
                $this->candidateRef($top),
                $this->candidateRef($second),
            ]));
            return [
                'replies' => ["I want to make sure I get this right — are you asking about \"{$top['label']}\" or \"{$second['label']}\"? Let me know which one and I'll help from there."],
                'escalate' => false,
                'context' => null,
            ];
        }

        return ['replies' => [$this->answerFromCandidate($top, $state['last_topic'])], 'escalate' => false, 'context' => null];
    }

    private function getConversationContext(): array
    {
        $messages = LiveChatMessage::where('chat_key', $this->chatKey)->orderByDesc('id')->limit(10)->get()->reverse()->toArray();
        return $messages;
    }

    private function getRecentUserMessages(int $limit = 5): array
    {
        return LiveChatMessage::where('chat_key', $this->chatKey)
            ->where('sender', 'customer')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('message')
            ->filter()
            ->values()
            ->toArray();
    }

    private function isFollowUpQuestion(string $lower, ?string $lastTopic): bool
    {
        if ($lastTopic === null) return false;
        // Short queries like "how much", "how long", "what about it", "and delivery?", "price?"
        if (str_word_count($lower) <= 4) return true;
        // Contains referential pronouns
        $pronouns = ['it','this','that','them','those','these','more','also','delivery','price','fee','how much','how long','when','where'];
        foreach ($pronouns as $p) {
            if (str_contains($lower, $p)) return true;
        }
        return false;
    }

    private function isHelpRequest(string $lower): bool
    {
        $helpPhrases = [
            'help', 'what can you do', 'what do you do', 'how do you work',
            'what are you capable of', 'tell me about yourself', 'capabilities',
            'what can i ask', 'how can you help', 'your features', 'help me'
        ];
        return $this->matchesAny($lower, $helpPhrases);
    }

    private function buildHelpResponse(): string
    {
        return "I'm the Luntiang H.A.P.A.G. Assistant 🌿 Here's what I can help you with:\n\n" .
               "📦 **Orders & Products**\n" .
               "• Track your order\n" .
               "• Product information and availability\n" .
               "• Varieties, pack sizes, and bundles\n" .
               "• Harvest-on-demand information\n\n" .
               "🛡️ **Freshness & Returns**\n" .
               "• Submit a freshness complaint\n" .
               "• Check refund eligibility\n" .
               "• Start a return or refund\n" .
               "• Return policy information\n\n" .
               "👤 **Account Help**\n" .
               "• Create an account\n" .
               "• Log in assistance\n" .
               "• Reset your password\n" .
               "• Update your profile\n\n" .
               "🎫 **Support Tickets**\n" .
               "• Submit a support ticket\n" .
               "• Check ticket status\n" .
               "• Get help with issues\n\n" .
               "💬 **Quick Actions**\n" .
               "• Type 'talk to agent' to reach a human\n" .
               "• Use the quick action buttons above\n\n" .
               "Just tell me what you need and I'll point you in the right direction! 😊";
    }

    private function candidateRef(array $candidate): array
    {
        return $candidate['type'] === 'service'
            ? ['type' => 'service', 'key' => $candidate['key'], 'label' => $candidate['label']]
            : ['type' => 'knowledge', 'id' => $candidate['id'], 'label' => $candidate['label']];
    }

    private function answerFromCandidate(array $candidate, ?string $lastTopic): string
    {
        if ($candidate['type'] === 'service') {
            $topicKey = 'service:' . $candidate['key'];
            $reply = $this->withRepeatAwareness($topicKey, $lastTopic, $this->buildServiceReply($candidate['key']));
            $this->rememberTopic($topicKey);
            return $reply;
        }

        $topicKey = 'topic:' . $candidate['id'];
        $topicDef = $this->kb['knowledge'][$candidate['id']] ?? [];
        if (($topicDef['id'] ?? null) === 'forgot_password') {
            $answer = $this->buildForgotPasswordReply();
        } elseif (!empty($topicDef['auth_aware'])) {
            $answer = $this->authAwareAnswer($topicDef['id']);
        } else {
            $answer = $candidate['answer'];
        }
        $reply = $this->withRepeatAwareness($topicKey, $lastTopic, $answer);

        $group = $this->kb['knowledge'][$candidate['id']]['group'] ?? null;
        $lastCategory = $this->lastTopicCategory($lastTopic);
        if ($group === 'attribute' && $lastCategory !== null) {
            $reply = "For {$lastCategory['display']}: " . $reply;
            $this->rememberTopic('topic:' . $lastCategory['index']);
            return $reply;
        }

        $this->rememberTopic($topicKey);
        return $reply;
    }

    private function authAwareAnswer(string $topicId): string
    {
        if ($topicId === 'login_help') {
            return $this->isLoggedIn
                ? "You're already signed in to your Luntiang H.A.P.A.G. account.\n\nIf you'd like to sign in using another account, please log out first.\n\nAfter logging out, return to the Home page and click the Login button in the upper-right corner."
                : "You're currently browsing as a guest.\n\n{$this->loginInstructions()}\n\nAfter logging in, you'll be able to submit Support Tickets, Freshness Requests, and Return & Refund Requests.";
        }

        if ($topicId === 'create_account') {
            return $this->isLoggedIn
                ? "You're already signed in to your Luntiang H.A.P.A.G. account.\n\nIf you'd like to create a new account, please log out first.\n\nAfter logging out, return to the Home page and click the Register button next to Login in the upper-right corner."
                : "You're currently browsing as a guest.\n\nTo create an account, return to the Home page and click the Register button next to Login in the upper-right corner.\n\nOnce you're registered and logged in, you'll be able to submit Support Tickets, Freshness Requests, and Return & Refund Requests.";
        }

        $index = $this->kbIndexById[$topicId] ?? null;
        return $index !== null ? $this->kb['knowledge'][$index]['answer'] : "";
    }

    private function buildForgotPasswordReply(): string
    {
        if ($this->isLoggedIn) {
            return "You're already logged in to your Luntiang H.A.P.A.G. account.\n\nIf you simply want to change your password, you can do so from your Customer Dashboard --> Show Details.\n\nIf you're trying to access another account, please log out first, then use the \"Forgot Password?\" option on the Login page.";
        }

        $this->setState(true, 'offer', json_encode(['kind' => 'password_reset_guide']));
        return "No problem! I can help you reset your password.\n\nTo begin, return to the Login page and click the \"Forgot Password?\" link.\n\nEnter the email address associated with your account and select \"Send Reset Link.\"\n\nSince this project is currently running in Development Mode, you'll see a Development Email Preview instead of receiving a real email.\n\nFrom there, click the \"Reset Password\" button in the preview to create a new password.\n\nWould you like me to guide you through each step?";
    }

    private function passwordResetStepText(int $step): string
    {
        $body = match ($step) {
            1 => "Step 1: Go to the Login page.",
            2 => "Step 2: Click \"Forgot Password?\" — you'll find it right below the password field.",
            3 => "Step 3: Enter the email address connected to your account.",
            4 => "Step 4: Click \"Send Reset Link.\"\n\n(Quick heads-up: the reset link can only be used once and expires after a while — if that happens, you can just request a new one.)",
            5 => "Step 5: Since this project is currently running in Development Mode, you won't get a real email — instead, a Development Email Preview will appear right on the page after you send the link.",
            6 => "Step 6: In that Development Email Preview, click the \"Reset Password\" button.",
            7 => "Step 7: Create a new password and confirm it. Pick something secure that you're not using elsewhere.",
            8 => "Step 8: Once your password is reset, return to the Login page and sign in with your new password. 🎉 That's it — you're all set! Let me know if there's anything else I can help with.",
            default => "Looks like we've already finished the password reset walkthrough! Let me know if there's anything else I can help with.",
        };

        if ($step >= 1 && $step <= 7) {
            $body .= "\n\nLet me know when you're ready for the next step.";
        }
        return $body;
    }

    private function resolvePasswordResetStep(string $lower, ?string $pendingContextJson): array
    {
        $context = json_decode($pendingContextJson ?? '[]', true) ?: [];
        $step = (int)($context['step'] ?? 1);

        if ($this->matchesAny($lower, [
            "don't see the email", 'dont see the email', 'no email', "didn't get an email",
            "didn't get the email", "haven't received", 'havent received', "can't find the email",
            'where is the email', 'no email received', 'check my email', "email hasn't arrived",
        ])) {
            $this->setState(true, 'password_reset_step', json_encode(['step' => $step]));
            return [
                'replies' => ["In the current development environment, a real email isn't sent. Instead, the system displays a Development Email Preview immediately after you submit your email address. From there, you can click the \"Reset Password\" button to continue.\n\nWhenever you're ready, just say \"next\" and I'll pick up right where we left off."],
                'escalate' => false,
                'context' => null,
            ];
        }

        if ($this->isNegative($lower)) {
            $this->setState(true, null, null);
            return [
                'replies' => ["No problem — we can pick this back up anytime. Just say \"forgot password\" whenever you're ready, or let me know if there's anything else I can help with."],
                'escalate' => false,
                'context' => null,
            ];
        }

        $wantsNext = $this->isAffirmative($lower) || $this->matchesAny($lower, ['next', 'continue', 'then what', 'what next', 'go on', 'next step', 'ready']);

        if ($wantsNext) {
            $step++;
            $reply = $this->passwordResetStepText($step);
            if ($step >= 8) {
                $this->setState(true, null, null);
            } else {
                $this->setState(true, 'password_reset_step', json_encode(['step' => $step]));
            }
            return ['replies' => [$reply], 'escalate' => false, 'context' => null];
        }

        $candidates = $this->rankedCandidates($lower);
        if (!empty($candidates)) {
            $answer = $this->answerFromCandidate($candidates[0], null);
            $this->setState(true, 'password_reset_step', json_encode(['step' => $step]));
            return ['replies' => [$answer], 'escalate' => false, 'context' => null];
        }

        $this->setState(true, 'password_reset_step', json_encode(['step' => $step]));
        return [
            'replies' => ["Sorry, I didn't quite get that — if you want to keep walking through the password reset, just say \"next\". Or ask me anything else!"],
            'escalate' => false,
            'context' => null,
        ];
    }

    private function lastTopicCategory(?string $lastTopic): ?array
    {
        if ($lastTopic === null || !str_starts_with($lastTopic, 'topic:')) {
            return null;
        }
        $id = (int)substr($lastTopic, 6);
        $topic = $this->kb['knowledge'][$id] ?? null;
        if ($topic === null || ($topic['group'] ?? null) !== 'category') {
            return null;
        }
        return ['display' => $topic['display'] ?? $topic['keywords'][0], 'index' => $id];
    }

    private function getState(): array
    {
        $row = ChatBotState::where('chat_key', $this->chatKey)->first();
        if (!$row) {
            ChatBotState::create(['chat_key' => $this->chatKey, 'bot_active' => true]);
            return ['bot_active' => true, 'pending_intent' => null, 'pending_context' => null, 'last_topic' => null];
        }
        return [
            'bot_active' => (bool)$row->bot_active,
            'pending_intent' => $row->pending_intent,
            'pending_context' => $row->pending_context,
            'last_topic' => $row->last_topic,
        ];
    }

    private function setState(bool $active, ?string $pendingIntent, ?string $pendingContext): void
    {
        ChatBotState::updateOrCreate(
            ['chat_key' => $this->chatKey],
            ['bot_active' => $active, 'pending_intent' => $pendingIntent, 'pending_context' => $pendingContext]
        );
    }

    private function rememberTopic(string $topicKey): void
    {
        ChatBotState::updateOrCreate(
            ['chat_key' => $this->chatKey],
            ['bot_active' => true, 'last_topic' => $topicKey]
        );
    }

    private function withRepeatAwareness(string $topicKey, ?string $lastTopic, string $reply): string
    {
        if ($lastTopic !== null && $lastTopic === $topicKey) {
            return "As I mentioned, here's that info again:\n\n{$reply}\n\nIf that doesn't fully answer things, let me know more specifically what's going on, or I can connect you with a live agent.";
        }
        return $reply;
    }

    public function reactivate(): void
    {
        $this->setState(true, null, null);
    }

    private function resolveDisambiguation(string $lower, ?string $pendingContextJson, ?string $lastTopic): array
    {
        $this->setState(true, null, null);
        $options = json_decode($pendingContextJson ?? '[]', true) ?: [];

        if (count($options) === 2) {
            [$first, $second] = $options;
            $mentionsFirst = str_contains($lower, strtolower($first['label'])) || $this->matchesAny($lower, ['first', 'the first one', 'option 1', 'option a']);
            $mentionsSecond = str_contains($lower, strtolower($second['label'])) || $this->matchesAny($lower, ['second', 'the second one', 'option 2', 'option b']);

            if ($mentionsFirst && !$mentionsSecond) {
                return ['replies' => [$this->answerFromRef($first, $lastTopic)], 'escalate' => false, 'context' => null];
            }
            if ($mentionsSecond && !$mentionsFirst) {
                return ['replies' => [$this->answerFromRef($second, $lastTopic)], 'escalate' => false, 'context' => null];
            }
        }

        return $this->respond($lower);
    }

    private function answerFromRef(array $ref, ?string $lastTopic): string
    {
        if ($ref['type'] === 'service') {
            $topicKey = 'service:' . $ref['key'];
            $reply = $this->withRepeatAwareness($topicKey, $lastTopic, $this->buildServiceReply($ref['key']));
        } else {
            $topicDef = $this->kb['knowledge'][$ref['id']] ?? null;
            if ($topicDef === null) {
                return "Sorry, I lost track of that — could you ask again?";
            }
            if (($topicDef['id'] ?? null) === 'forgot_password') {
                $answer = $this->buildForgotPasswordReply();
            } else {
                $answer = !empty($topicDef['auth_aware']) ? $this->authAwareAnswer($topicDef['id']) : $topicDef['answer'];
            }
            $topicKey = 'topic:' . $ref['id'];
            $reply = $this->withRepeatAwareness($topicKey, $lastTopic, $answer);
        }
        $this->rememberTopic($topicKey);
        return $reply;
    }

    private function resolveOffer(string $lower, ?string $pendingContextJson, ?string $lastTopic): array
    {
        $this->setState(true, null, null);
        $context = json_decode($pendingContextJson ?? '[]', true) ?: [];
        $kind = $context['kind'] ?? null;

        if ($this->isNegative($lower)) {
            return [
                'replies' => ["No problem — just let me know if you change your mind, or if there's anything else I can help with."],
                'escalate' => false,
                'context' => null,
            ];
        }

        if ($this->isAffirmative($lower) && $kind === 'login_then_goal' && !empty($context['service']) && isset($this->kb['services'][$context['service']])) {
            $service = $this->kb['services'][$context['service']];
            $reply = $this->loginInstructions() . "\n\nOnce you're logged in, open your Customer Dashboard and select {$service['label']}.";
            if (!empty($service['requirements'])) {
                $reply .= "\n\nYou'll need:\n" . implode("\n", array_map(fn($r) => "• {$r}", $service['requirements']));
            }
            $this->rememberTopic('service:' . $context['service']);
            return ['replies' => [$reply], 'escalate' => false, 'context' => null];
        }

        if ($this->isAffirmative($lower) && $kind === 'password_reset_guide') {
            $this->setState(true, 'password_reset_step', json_encode(['step' => 1]));
            return ['replies' => [$this->passwordResetStepText(1)], 'escalate' => false, 'context' => null];
        }

        return $this->respond($lower);
    }

    private function loginInstructions(): string
    {
        return "To sign in, return to the Home page and click the Login button located in the upper-right corner.";
    }

    private function isAffirmative(string $lower): bool
    {
        $words = $this->kb['affirmative_words'] ?? [];
        return in_array($lower, $words, true) || $this->containsWholeWord($lower, $words);
    }

    private function isNegative(string $lower): bool
    {
        $words = $this->kb['negative_words'] ?? [];
        return in_array($lower, $words, true) || $this->containsWholeWord($lower, $words);
    }

    private function containsWholeWord(string $lower, array $words): bool
    {
        if (empty($words)) {
            return false;
        }
        $sorted = $words;
        usort($sorted, fn($a, $b) => strlen($b) <=> strlen($a));
        foreach ($sorted as $word) {
            if (preg_match('/(?:^|\s)' . preg_quote($word, '/') . '(?:$|\s)/', $lower)) {
                return true;
            }
        }
        return false;
    }

    private function resolveDamageTimingFollowUp(string $lower, ?string $originalMessage): array
    {
        $this->setState(true, null, null);

        $onArrival = $this->matchesAny($lower, [
            'on delivery', 'on arrival', 'when it arrived', 'arrived damaged', 'arrived broken',
            'already', 'right away', 'out of the box', 'from the start', 'from the beginning',
            'wrong item', 'missing part', 'wrong order',
        ]);
        $afterUse = $this->matchesAny($lower, [
            'after use', 'after using', 'while using', 'over time', 'later', 'after a while',
            'weeks', 'months', 'normal use', 'been using it',
        ]);

        if ($onArrival && !$afterUse) {
            return ['replies' => [$this->buildServiceReply('return')], 'escalate' => false, 'context' => null];
        }

        if ($afterUse && !$onArrival) {
            return ['replies' => [$this->buildServiceReply('freshness_guarantee')], 'escalate' => false, 'context' => null];
        }

        $return = $this->kb['services']['return'] ?? ['label'=>'Return'];
        $freshnessGuarantee = $this->kb['services']['freshness_guarantee'] ?? ['label'=>'Freshness Guarantee'];
        return [
            'replies' => [
                "To point you to the right form: if the issue was there when it arrived (wrong/damaged/missing parts), that's a {$return['label']}. If it developed after normal use (a quality issue), that's a {$freshnessGuarantee['label']}. Which sounds closer to what happened?",
            ],
            'escalate' => false,
            'context' => null,
        ];
    }

    private function normalize(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace("/[^a-z0-9' ]+/", ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return $this->applySynonyms(trim($text));
    }

    private function applySynonyms(string $text): string
    {
        $synonyms = $this->kb['synonyms'] ?? [];
        $phrases = array_keys($synonyms);
        usort($phrases, fn($a, $b) => strlen($b) <=> strlen($a));
        foreach ($phrases as $phrase) {
            $text = str_replace($phrase, $synonyms[$phrase], $text);
        }
        return $text;
    }

    private function matchesAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    private function isPureGreeting(string $lower): bool
    {
        return in_array($lower, $this->kb['greetings'] ?? [], true);
    }

    private function isGratitude(string $lower): bool
    {
        return in_array($lower, $this->kb['gratitude'] ?? [], true);
    }

    private function randomFrom(array $items, string $default): string
    {
        if (empty($items)) {
            return $default;
        }
        return $items[array_rand($items)];
    }

    private function isVagueHelpRequest(string $lower): bool
    {
        $vaguePhrases = [
            'i need help', 'i need assistance', 'can you help me', 'can you help',
            'i have a problem', 'i have an issue', 'help me please', 'need some help',
            'i have a question', "i'm having trouble", 'im having trouble',
        ];
        if (!$this->matchesAny($lower, $vaguePhrases)) {
            return false;
        }
        $hasSignal = $this->bestServiceMatch($lower) !== null || $this->bestKnowledgeMatch($lower) !== null;
        return !$hasSignal;
    }

    private function isAmbiguousDamageReport(string $lower): bool
    {
        $damageWords = ['crack', 'cracked', 'broke', 'broken', 'chip', 'chipped', 'scratch', 'scratched', 'dent', 'dented'];
        if (!$this->matchesAny($lower, $damageWords)) {
            return false;
        }
        $alreadyClear = $this->matchesAny($lower, [
            'on delivery', 'on arrival', 'when it arrived', 'arrived damaged', 'arrived broken',
            'after use', 'after using', 'over time', 'normal use', 'weeks ago', 'months ago',
        ]);
        return !$alreadyClear;
    }

    private function resolveContextualFollowUp(string $lower, ?string $lastTopic): ?array
    {
        $domain = $this->domainForLastTopic($lastTopic);
        if ($domain !== null) {
            $target = $this->matchAliasMap($lower, $this->kb['contextual_word_aliases'][$domain] ?? []);
            if ($target !== null) {
                $reply = $this->replyForAliasTarget($target, $lastTopic);
                if ($reply !== null) {
                    return $reply;
                }
            }
        }

        $aliasId = $this->matchTopicAlias($lower);
        if ($aliasId !== null) {
            $reply = $this->replyForAliasTarget($aliasId, $lastTopic);
            if ($reply !== null) {
                return $reply;
            }
        }

        if ($lastTopic !== null && $this->matchesAny(" {$lower} ", array_map(fn($p) => " {$p} ", $this->kb['referential_pronouns'] ?? []))) {
            $anchor = $this->anchorForTopic($lastTopic);
            if ($anchor !== null) {
                $augmented = $this->applySynonyms($lower . ' ' . $anchor);
                $reCandidates = $this->rankedCandidates($augmented);
                if (!empty($reCandidates)) {
                    return ['replies' => [$this->answerFromCandidate($reCandidates[0], $lastTopic)], 'escalate' => false, 'context' => null];
                }
            }
        }

        return null;
    }

    private function replyForAliasTarget(string $target, ?string $lastTopic): ?array
    {
        if (str_starts_with($target, 'svc:')) {
            $key = substr($target, 4);
            if (!isset($this->kb['services'][$key])) {
                return null;
            }
            $candidate = ['type' => 'service', 'key' => $key, 'label' => $this->kb['services'][$key]['label']];
            return ['replies' => [$this->answerFromCandidate($candidate, $lastTopic)], 'escalate' => false, 'context' => null];
        }

        if (!isset($this->kbIndexById[$target])) {
            return null;
        }
        $index = $this->kbIndexById[$target];
        $candidate = ['type' => 'knowledge', 'id' => $index, 'answer' => $this->kb['knowledge'][$index]['answer']];
        return ['replies' => [$this->answerFromCandidate($candidate, $lastTopic)], 'escalate' => false, 'context' => null];
    }

    private function domainForLastTopic(?string $lastTopic): ?string
    {
        if ($lastTopic === null) {
            return null;
        }
        if (str_starts_with($lastTopic, 'service:')) {
            $key = substr($lastTopic, 8);
            return $this->kb['services'][$key]['domain'] ?? null;
        }
        if (str_starts_with($lastTopic, 'topic:')) {
            $id = (int)substr($lastTopic, 6);
            return $this->kb['knowledge'][$id]['domain'] ?? null;
        }
        return null;
    }

    private function matchAliasMap(string $lower, array $aliases): ?string
    {
        if (empty($aliases)) {
            return null;
        }
        $words = array_keys($aliases);
        usort($words, fn($a, $b) => strlen($b) <=> strlen($a));
        foreach ($words as $word) {
            if (preg_match('/(?:^|\s)' . preg_quote($word, '/') . '(?:$|\s)/', $lower)) {
                return $aliases[$word];
            }
        }
        return null;
    }

    private function matchTopicAlias(string $lower): ?string
    {
        return $this->matchAliasMap($lower, $this->kb['topic_word_aliases'] ?? []);
    }

    private function anchorForTopic(string $lastTopic): ?string
    {
        if (str_starts_with($lastTopic, 'service:')) {
            $key = substr($lastTopic, 8);
            return $this->kb['services'][$key]['keywords'][0] ?? null;
        }
        if (str_starts_with($lastTopic, 'topic:')) {
            $id = (int)substr($lastTopic, 6);
            $topic = $this->kb['knowledge'][$id] ?? null;
            return $topic['display'] ?? ($topic['keywords'][0] ?? null);
        }
        return null;
    }

    private function bestServiceMatch(string $lower): ?array
    {
        $best = null;
        foreach ($this->kb['services'] as $key => $service) {
            if ($key === 'live_agent') {
                continue;
            }
            $score = $this->scoreKeywords($lower, $service['keywords'] ?? []);
            if ($score > 0 && (!$best || $score > $best['score'])) {
                $best = ['key' => $key, 'score' => $score, 'label' => $service['label']];
            }
        }
        return $best;
    }

    private function bestKnowledgeMatch(string $lower): ?array
    {
        $best = null;
        foreach ($this->kb['knowledge'] as $i => $topic) {
            $score = $this->scoreKeywords($lower, $topic['keywords'] ?? []);
            if ($score > 0 && (!$best || $score > $best['score'])) {
                $best = ['id' => $i, 'answer' => $topic['answer'], 'score' => $score, 'label' => $topic['keywords'][0]];
            }
        }
        return $best;
    }

    private function rankedCandidates(string $lower): array
    {
        $candidates = [];

        foreach ($this->kb['services'] as $key => $service) {
            if ($key === 'live_agent') {
                continue;
            }
            $score = $this->scoreKeywords($lower, $service['keywords'] ?? []);
            if ($score > 0) {
                $candidates[] = [
                    'type' => 'service', 'key' => $key, 'score' => $score, 'label' => $service['label'],
                ];
            }
        }

        foreach ($this->kb['knowledge'] as $i => $topic) {
            $score = $this->scoreKeywords($lower, $topic['keywords'] ?? []);
            if ($score > 0) {
                $candidates[] = [
                    'type' => 'knowledge', 'id' => $i, 'score' => $score,
                    'label' => ucfirst($topic['keywords'][0] ?? ''), 'answer' => $topic['answer'],
                ];
            }
        }

        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
        return $candidates;
    }

    private function scoreKeywords(string $lower, array $keywords): float
    {
        $score = 0;
        foreach ($keywords as $kw) {
            if (str_contains($lower, $kw)) {
                $score += max(1, substr_count(trim($kw), ' ') + 1);
            }
        }
        return $score;
    }

    private function buildServiceReply(string $key): string
    {
        $service = $this->kb['services'][$key] ?? null;
        if (!$service) return "Service not found.";

        if (!empty($service['requires_login']) && !$this->isLoggedIn) {
            $this->setState(true, 'offer', json_encode(['kind' => 'login_then_goal', 'service' => $key]));
            return "{$service['label']}s require a Luntiang H.A.P.A.G. account.\n\nWould you like me to show you how to log in?";
        }

        $parts = [$service['explain']];
        if (!empty($service['action'])) {
            $parts[] = $service['action'];
        }
        return implode("\n\n", $parts);
    }

    private function explainAllServices(): string
    {
        $lines = ["No problem — here's a quick breakdown of each option:\n"];
        foreach ($this->kb['services'] as $key => $service) {
            if ($key === 'live_agent') {
                continue;
            }
            $lines[] = "• {$service['label']}: {$service['explain']}";
        }
        $lines[] = "\nJust tell me what happened and I'll point you to the right one.";
        return implode("\n", $lines);
    }
}
