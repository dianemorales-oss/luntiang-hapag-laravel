<?php
/**
 * includes/chatbot-engine.php
 * ------------------------------------------------------------------
 * Luntiang H.A.P.A.G. AI Customer Service Assistant — intent matching engine.
 *
 * This is the first responder in Live Chat. It never replaces the
 * existing service modules (Support Ticket, Warranty, Return &
 * Refund, Live Chat, Contact Support, Feedback) — its only job is to
 * (a) answer general knowledge questions conversationally, and
 * (b) figure out which of those modules a customer actually needs
 * and guide them there, asking a follow-up question first when the
 * request is genuinely ambiguous (e.g. "my chair cracked" could be a
 * Warranty issue or a Return issue depending on when the damage
 * happened).
 *
 * Extensibility: the keyword/answer data this engine matches against
 * lives entirely in chatbot-knowledge.php. To teach the assistant a
 * new topic or routing rule, add an entry there — no changes needed
 * here.
 *
 * Usage (see chat-send.php):
 *   $engine = new ChatbotEngine($conn, $chatKey);
 *   $result = $engine->respond($customerMessage);
 *   // $result = [
 *   //   'replies'  => [string, ...]   one or more bot message bubbles
 *   //   'escalate' => bool           true => hand off to a human agent
 *   // ]
 * ------------------------------------------------------------------
 */

class ChatbotEngine
{
    private PDO $conn;
    private string $chatKey;
    private array $kb;
    private bool $isLoggedIn;

    /** Maps a knowledge entry's stable 'id' (e.g. 'beds') to its array index, for O(1) lookup by ChatbotEngine::topicById(). */
    private array $kbIndexById = [];

    /**
     * @param bool $isLoggedIn Whether the current visitor has an active
     *     Luntiang H.A.P.A.G. session ($_SESSION['user_id']). Drives
     *     auth-aware replies (login/registration guidance, and gated
     *     service routing for Support Ticket / Warranty / Return &
     *     Refund) — see OBJECTIVE #3 and #5.
     */
    public function __construct(PDO $conn, string $chatKey, bool $isLoggedIn = false)
    {
        $this->conn = $conn;
        $this->chatKey = $chatKey;
        $this->isLoggedIn = $isLoggedIn;
        $this->kb = require __DIR__ . '/chatbot-knowledge.php';

        foreach ($this->kb['knowledge'] as $i => $topic) {
            if (!empty($topic['id'])) {
                $this->kbIndexById[$topic['id']] = $i;
            }
        }
    }

    /**
     * Returns the greeting shown at the very start of a new
     * conversation (inserted once by live-chat.php as a bot message).
     */
    // In chatbot-engine.php, update the greeting() method:
public static function greeting(): string
{
    $greetings = [
        "Hi, I'm the Luntiang H.A.P.A.G. Assistant 🥬 I can answer questions about our products, orders, delivery, and freshness — and I'll help point you to the right form if you need one. What can I help you with today?",
        "Welcome to Luntiang H.A.P.A.G.! 🌱 I'm your AI assistant here to help with orders, product info, delivery questions, and more. What brings you here today?",
        "Hello there! 👋 I'm the Luntiang H.A.P.A.G. Assistant. Need help with an order, have questions about our organic lettuce, or just want to know more about our farm? I'm here to help — just let me know what you need!"
    ];
    return $greetings[array_rand($greetings)];
}

    /**
     * Main entry point: process one customer message and decide how
     * the bot should reply.
     */
    public function respond(string $text): array
    {
        $lower = $this->normalize($text);
        $state = $this->getState();

        // ------------------------------------------------------------
        // 1. Let the customer bring the assistant back at any time,
        //    even if it stood down earlier (agent request, or a human
        //    admin has replied). This check runs regardless of
        //    bot_active so it isn't silently swallowed.
        // ------------------------------------------------------------
        if ($this->matchesAny($lower, $this->kb['reactivate_keywords'])) {
            $this->setState(true, null, null);
            return [
                'replies' => ["I'm back! What can I help you with?"],
                'escalate' => false,
                'context' => null,
            ];
        }

        // ------------------------------------------------------------
        // 2. If a human agent already took over, or the customer has
        //    stopped needing the bot, stay silent.
        // ------------------------------------------------------------
        if (!$state['bot_active']) {
            return ['replies' => [], 'escalate' => false, 'context' => null];
        }

        // ------------------------------------------------------------
        // 3. "Help" command - show what the bot can do
        // ------------------------------------------------------------
        if ($this->isHelpRequest($lower)) {
            return [
                'replies' => [$this->buildHelpResponse()],
                'escalate' => false,
                'context' => null,
            ];
        }

        // ------------------------------------------------------------
        // 4. If we're mid-way through a clarifying question, interpret
        //    this message as the answer to it rather than a fresh intent.
        // ------------------------------------------------------------
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

        // ------------------------------------------------------------
        // 5. Standalone greetings ("hi", "good morning") and thank-yous
        //    ("thanks!") get a friendly, varied reply instead of being
        //    run through intent matching.
        // ------------------------------------------------------------
        if ($this->isPureGreeting($lower)) {
            return ['replies' => [$this->randomFrom($this->kb['greeting_responses'] ?? [], "Hi there! How can I help you today?")], 'escalate' => false, 'context' => null];
        }
        if ($this->isGratitude($lower)) {
            return ['replies' => [$this->randomFrom($this->kb['gratitude_responses'] ?? [], "You're very welcome!")], 'escalate' => false, 'context' => null];
        }

        // ------------------------------------------------------------
        // 6. Explicit request for a human agent -> escalate immediately.
        //    Store conversation context for the agent.
        // ------------------------------------------------------------
        if ($this->matchesAny($lower, $this->kb['services']['live_agent']['keywords'])) {
            $context = $this->getConversationContext();
            $this->setState(false, null, null);
            return [
                'replies' => ["I couldn't fully resolve this myself, so I'll connect you with one of our customer support representatives. Someone will jump into this chat shortly — feel free to describe your issue in the meantime so they have context. If you'd rather keep chatting with me instead, just say \"talk to the assistant again\"."],
                'escalate' => true,
                'context' => $context,
            ];
        }

        // ------------------------------------------------------------
        // 7. Vague help requests ("I need help", "can you help me")
        // ------------------------------------------------------------
        if ($this->isVagueHelpRequest($lower)) {
            return [
                'replies' => ["Of course — happy to help. Could you tell me a little more about what's going on? For example: is this about an order you placed, a product that arrived with a problem, your account, or something else?"],
                'escalate' => false,
                'context' => null,
            ];
        }

        // ------------------------------------------------------------
        // 8. Ambiguous "damage/crack/broken" language
        // ------------------------------------------------------------
        if ($this->isAmbiguousDamageReport($lower)) {
            $this->setState(true, 'damage_timing', $text);
            return [
                'replies' => ["I can help with that. Was the damage already there when the item arrived, or did it happen after you'd been using it for a while?"],
                'escalate' => false,
                'context' => null,
            ];
        }

        // ------------------------------------------------------------
        // 9. Ambiguous "refund" request
        // ------------------------------------------------------------
        if ($this->matchesAny($lower, ['refund']) && !$this->matchesAny($lower, ['damaged', 'wrong item', 'missing part', 'defective', 'wrong order'])) {
            $this->setState(true, 'damage_timing', $text);
            return [
                'replies' => ["Happy to help sort that out. Did your order arrive wrong, damaged, or missing parts — or did the issue develop after you'd had it a while?"],
                'escalate' => false,
                'context' => null,
            ];
        }

        // ------------------------------------------------------------
        // 10. "I don't know which form to use"
        // ------------------------------------------------------------
        if ($this->matchesAny($lower, ["don't know which", "dont know which", "which form", "which one should i use", "not sure which"])) {
            return ['replies' => [$this->explainAllServices()], 'escalate' => false, 'context' => null];
        }

        // ------------------------------------------------------------
        // 11. Rank every service + knowledge intent that matched at all
        // ------------------------------------------------------------
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

        // Two (or more) clearly different intents are close enough in
        // confidence that picking one would be a guess — ask instead.
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

        // Confident enough — answer with the top candidate.
        return ['replies' => [$this->answerFromCandidate($top, $state['last_topic'])], 'escalate' => false, 'context' => null];
    }

    /**
     * Get conversation context for agent escalation
     */
    private function getConversationContext(): array
    {
        $stmt = $this->conn->prepare("
            SELECT sender, message, created_at 
            FROM live_chat_messages 
            WHERE chat_key = ? 
            ORDER BY id DESC 
            LIMIT 10
        ");
        $stmt->execute([$this->chatKey]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_reverse($messages);
    }

    /**
     * Check if the user is asking for help
     */
    private function isHelpRequest(string $lower): bool
    {
        $helpPhrases = [
            'help', 'what can you do', 'what do you do', 'how do you work',
            'what are you capable of', 'tell me about yourself', 'capabilities',
            'what can i ask', 'how can you help', 'your features', 'help me'
        ];
        return $this->matchesAny($lower, $helpPhrases);
    }

    /**
     * Build the help response showing bot capabilities
     */
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

    /**
     * A compact reference to a ranked candidate, just enough to resolve
     * it again once the customer answers a disambiguation question.
     */
    private function candidateRef(array $candidate): array
    {
        return $candidate['type'] === 'service'
            ? ['type' => 'service', 'key' => $candidate['key'], 'label' => $candidate['label']]
            : ['type' => 'knowledge', 'id' => $candidate['id'], 'label' => $candidate['label']];
    }

    /**
     * Builds the actual reply text for a ranked candidate (service
     * explanation, or knowledge-base answer), applying repeat-awareness
     * and remembering the topic for next turn.
     */
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

        // A generic attribute question ("what colors are available?")
        // right after we were discussing a specific category should
        // keep talking about that category, not read as a brand-new,
        // unrelated topic.
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

    /**
     * Dynamic answer for knowledge topics marked 'auth_aware' (login
     * and registration help) — the assistant needs to know the
     * Luntiang H.A.P.A.G. session state to answer these usefully, since
     * "where do I log in" means something different to a guest than
     * to someone already signed in.
     */
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

    /**
     * Auth-aware entry point for the "forgot my password" intent
     */
    private function buildForgotPasswordReply(): string
    {
        if ($this->isLoggedIn) {
            return "You're already logged in to your Luntiang H.A.P.A.G. account.\n\nIf you simply want to change your password, you can do so from your Customer Dashboard --> Show Details.\n\nIf you're trying to access another account, please log out first, then use the \"Forgot Password?\" option on the Login page.";
        }

        $this->setState(true, 'offer', json_encode(['kind' => 'password_reset_guide']));
        return "No problem! I can help you reset your password.\n\nTo begin, return to the Login page and click the \"Forgot Password?\" link.\n\nEnter the email address associated with your account and select \"Send Reset Link.\"\n\nSince this project is currently running in Development Mode, you'll see a Development Email Preview instead of receiving a real email.\n\nFrom there, click the \"Reset Password\" button in the preview to create a new password.\n\nWould you like me to guide you through each step?";
    }

    /**
     * Text for one step of the guided password-reset walkthrough
     */
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

    /**
     * Resolves the next message once the customer is mid-way through
     * the guided password-reset walkthrough.
     */
    private function resolvePasswordResetStep(string $lower, ?string $pendingContextJson): array
    {
        $context = json_decode($pendingContextJson ?? '[]', true) ?: [];
        $step = (int)($context['step'] ?? 1);

        // Check if this is a question about not receiving the email
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

        // Check if they want to stop the walkthrough
        if ($this->isNegative($lower)) {
            $this->setState(true, null, null);
            return [
                'replies' => ["No problem — we can pick this back up anytime. Just say \"forgot password\" whenever you're ready, or let me know if there's anything else I can help with."],
                'escalate' => false,
                'context' => null,
            ];
        }

        // Check if they want to continue with the next step
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

        // This is a genuine question in the middle of the flow - answer it normally
        // WITHOUT appending the "next" message
        $candidates = $this->rankedCandidates($lower);
        if (!empty($candidates)) {
            $answer = $this->answerFromCandidate($candidates[0], null);
            $this->setState(true, 'password_reset_step', json_encode(['step' => $step]));
            return ['replies' => [$answer], 'escalate' => false, 'context' => null];
        }

        // If we couldn't find a match, just acknowledge and keep the walkthrough alive
        $this->setState(true, 'password_reset_step', json_encode(['step' => $step]));
        return [
            'replies' => ["I understand you're asking about something else. Whenever you're ready to continue with your password reset, just say \"next\" and I'll pick up where we left off. Is there something specific I can help you with?"],
            'escalate' => false,
            'context' => null,
        ];
    }

    /**
     * If $lastTopic points at a product-category knowledge entry
     * (group === 'category'), returns its index + display name so a
     * follow-up attribute question can be anchored back to it.
     */
    private function lastTopicCategory(?string $lastTopic): ?array
    {
        if ($lastTopic === null || !str_starts_with($lastTopic, 'topic:')) {
            return null;
        }
        $id = (int)substr($lastTopic, 6);
        $topic = $this->kb['knowledge'][$id] ?? null;
        if (!$topic || ($topic['group'] ?? null) !== 'category') {
            return null;
        }
        return ['index' => $id, 'display' => $topic['display'] ?? ($topic['keywords'][0] ?? 'that')];
    }

    // =====================================================================
    // Conversation state (persists the multi-turn clarification flow)
    // =====================================================================

    private function getState(): array
    {
        $stmt = $this->conn->prepare("SELECT bot_active, pending_intent, pending_context, last_topic FROM chat_bot_state WHERE chat_key = ?");
        $stmt->execute([$this->chatKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->conn->prepare("INSERT INTO chat_bot_state (chat_key, bot_active) VALUES (?, 1)")->execute([$this->chatKey]);
            return ['bot_active' => true, 'pending_intent' => null, 'pending_context' => null, 'last_topic' => null];
        }

        return [
            'bot_active' => (bool)$row['bot_active'],
            'pending_intent' => $row['pending_intent'],
            'pending_context' => $row['pending_context'],
            'last_topic' => $row['last_topic'],
        ];
    }

    private function setState(bool $active, ?string $pendingIntent, ?string $pendingContext): void
    {
        $stmt = $this->conn->prepare("
            INSERT INTO chat_bot_state (chat_key, bot_active, pending_intent, pending_context)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE bot_active = VALUES(bot_active),
                                     pending_intent = VALUES(pending_intent),
                                     pending_context = VALUES(pending_context)
        ");
        $stmt->execute([$this->chatKey, $active ? 1 : 0, $pendingIntent, $pendingContext]);
    }

    /**
     * Remembers which topic/service the assistant just answered with,
     * so the next turn can recognize "we already covered this" and
     * respond differently instead of repeating itself verbatim.
     */
    private function rememberTopic(string $topicKey): void
    {
        $stmt = $this->conn->prepare("
            INSERT INTO chat_bot_state (chat_key, bot_active, last_topic)
            VALUES (?, 1, ?)
            ON DUPLICATE KEY UPDATE last_topic = VALUES(last_topic)
        ");
        $stmt->execute([$this->chatKey, $topicKey]);
    }

    /**
     * If the customer's message matches the same topic we just answered
     * last turn, avoid a verbatim repeat: acknowledge that we already
     * covered it and invite them to ask something more specific or
     * escalate, rather than pasting the identical paragraph again.
     */
    private function withRepeatAwareness(string $topicKey, ?string $lastTopic, string $reply): string
    {
        if ($lastTopic !== null && $lastTopic === $topicKey) {
            return "As I mentioned, here's that info again:\n\n{$reply}\n\nIf that doesn't fully answer things, let me know more specifically what's going on, or I can connect you with a live agent.";
        }
        return $reply;
    }

    /**
     * Marks the bot inactive for this chat (e.g. after handing off to
     * a human, or once a form has been recommended and confirmed).
     * Exposed so chat-send.php can also reactivate the bot if a
     * customer starts a brand-new topic after a human reply.
     */
    public function reactivate(): void
    {
        $this->setState(true, null, null);
    }

    /**
     * Resolves a disambiguation question ("did you mean X or Y?") once
     * the customer answers. If their reply clearly points at one of the
     * two options (by label or first-choice wording), answer with that
     * one; otherwise treat the reply as a brand-new message so the
     * conversation doesn't get stuck.
     */
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

    /**
     * Rebuilds a full reply from the compact reference stored in
     * pending_context for a disambiguation question.
     */
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

    /**
     * Resolves a "would you like me to show you how to log in?" style
     * offer once the customer replies.
     */
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

    /**
     * The standard guest-facing login instructions
     */
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

    /**
     * Whole-word/phrase containment check
     */
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

    // =====================================================================
    // Clarification follow-up handling
    // =====================================================================

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

        $return = $this->kb['services']['return'];
        $freshnessGuarantee = $this->kb['services']['freshness_guarantee'];
        return [
            'replies' => [
                "To point you to the right form: if the issue was there when it arrived (wrong/damaged/missing parts), that's a {$return['label']}. If it developed after normal use (a quality issue), that's a {$freshnessGuarantee['label']}. Which sounds closer to what happened?",
            ],
            'escalate' => false,
            'context' => null,
        ];
    }

    // =====================================================================
    // Matching helpers
    // =====================================================================

    private function normalize(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace("/[^a-z0-9' ]+/", ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return $this->applySynonyms(trim($text));
    }

    /**
     * Rewrites known synonyms/variations to a single canonical phrase
     */
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

    /**
     * True only for a short, standalone greeting
     */
    private function isPureGreeting(string $lower): bool
    {
        return in_array($lower, $this->kb['greetings'] ?? [], true);
    }

    /**
     * True only for a short, standalone thank-you
     */
    private function isGratitude(string $lower): bool
    {
        return in_array($lower, $this->kb['gratitude'] ?? [], true);
    }

    /**
     * Picks one reply at random from a pool of equivalent variants
     */
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

    /**
     * Last-resort context resolution
     */
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

    /**
     * Resolves an alias map's target value into a ready-to-send reply.
     */
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

    /**
     * The domain a stored last_topic reference belongs to
     */
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

    /**
     * Looks up a bare/short follow-up word against an alias map
     */
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

    /**
     * Looks up a bare/short follow-up word against the global alias map
     */
    private function matchTopicAlias(string $lower): ?string
    {
        return $this->matchAliasMap($lower, $this->kb['topic_word_aliases'] ?? []);
    }

    /**
     * The canonical word/phrase for a stored last_topic reference
     */
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

    /**
     * Scores every service definition against the message
     */
    private function bestServiceMatch(string $lower): ?array
    {
        $best = null;
        foreach ($this->kb['services'] as $key => $service) {
            if ($key === 'live_agent') {
                continue;
            }
            $score = $this->scoreKeywords($lower, $service['keywords']);
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
            $score = $this->scoreKeywords($lower, $topic['keywords']);
            if ($score > 0 && (!$best || $score > $best['score'])) {
                $best = ['id' => $i, 'answer' => $topic['answer'], 'score' => $score, 'label' => $topic['keywords'][0]];
            }
        }
        return $best;
    }

    /**
     * All service + knowledge candidates that matched at all, sorted by
     * score descending.
     */
    private function rankedCandidates(string $lower): array
    {
        $candidates = [];

        foreach ($this->kb['services'] as $key => $service) {
            if ($key === 'live_agent') {
                continue;
            }
            $score = $this->scoreKeywords($lower, $service['keywords']);
            if ($score > 0) {
                $candidates[] = [
                    'type' => 'service', 'key' => $key, 'score' => $score, 'label' => $service['label'],
                ];
            }
        }

        foreach ($this->kb['knowledge'] as $i => $topic) {
            $score = $this->scoreKeywords($lower, $topic['keywords']);
            if ($score > 0) {
                $candidates[] = [
                    'type' => 'knowledge', 'id' => $i, 'score' => $score,
                    'label' => ucfirst($topic['keywords'][0]), 'answer' => $topic['answer'],
                ];
            }
        }

        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
        return $candidates;
    }

    /**
     * Weighted keyword scoring
     */
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

    /**
     * Builds the reply for a routed service.
     */
    private function buildServiceReply(string $key): string
    {
        $service = $this->kb['services'][$key];

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