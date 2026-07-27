<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove Account category FAQs from display (not delete, just set to hidden? Requirement says remove filter, but we filter in controller. Optionally delete Account FAQs or keep.)
        // We'll keep them but filtered out.

        $exists = DB::table('faqs')->where('question', 'like', '%What is a Support Ticket?%')->exists();
        if (!$exists) {
            DB::table('faqs')->insert([
                [
                    'question' => 'What is a Support Ticket?',
                    'answer' => "A Support Ticket is your direct line to our Luntiang H.A.P.A.G. support team. It is used to report issues, ask questions, or request help regarding orders, delivery, payments, product quality, or technical problems with the website.

Support Tickets are tracked with a unique Ticket ID, allowing both you and our team to follow the conversation, status updates, and resolution history.

Ticket Statuses:
- Open: New ticket, waiting for team review
- In Progress: Our team is currently working on your request
- Resolved: Issue has been addressed and solution provided
- Closed: Ticket is closed after resolution or by customer request

You can view all your support tickets in your Customer Dashboard under Support Requests Summary.",
                    'category' => 'Technical Support',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'question' => 'How do I create a Support Ticket?',
                    'answer' => "Creating a Support Ticket is easy:

1. Log in to your Luntiang H.A.P.A.G. account
2. Go to Customer Dashboard > Support, or visit Submit Ticket page (accessible via navigation or footer > Contact Support > Submit Ticket)
3. Fill in:
   - Subject: Brief summary of your issue
   - Category: Order Issue, Product Defect, Delivery Issue, Payment Issue, Website / Technical Issue, or Other
   - Priority: Low, Medium, High
   - Order Number (if related to an order, e.g., LH-0001)
   - Issue Description: Detailed explanation (max 1000 characters)
   - Attachment: Optional photos (JPG, PNG, PDF, max 5MB total) for proof
4. Review your information on confirmation page
5. Click Confirm to submit

You will receive a Ticket ID immediately and a notification when our team replies. You can track status in Customer Dashboard > Support Requests Summary and click View Details to see full conversation.",
                    'category' => 'Technical Support',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'question' => 'How are Support Tickets processed and what is the expected response time?',
                    'answer' => "Once you submit a ticket:

1. Acknowledgment: Ticket status becomes Open and appears in your dashboard within seconds
2. Assignment: Our support team reviews tickets in priority order – High priority (e.g., delivery failures, payment issues) are handled first
3. In Progress: Team updates status to In Progress while investigating. You may receive follow-up questions via ticket replies
4. Resolution: Once resolved, status becomes Resolved. You’ll get a real-time notification (bell icon) and optional browser notification if enabled
5. Closure: Ticket becomes Closed after 48 hours if no further reply, or you can close it manually

Expected Response Time:
- High Priority: Within 2-4 hours during business hours (8 AM - 8 PM, Open Everyday)
- Medium Priority: Within 12-24 hours
- Low Priority: Within 24-48 hours

Need urgent help? Use Live Chat for instant assistance during business hours – our chatbot maintains conversation context and can escalate to a human agent instantly with image upload support.

All ticket history is saved permanently under your account for future reference.",
                    'category' => 'Technical Support',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        DB::table('faqs')->where('question', 'like', '%Support Ticket%')->delete();
    }
};
