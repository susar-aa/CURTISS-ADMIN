Create a new web application named **Curtiss Client Manager**.

Tech stack: PHP 8+, MySQL, MVC architecture, HTML, CSS, JavaScript, Bootstrap 5.

Requirements:
- Fully responsive, mobile-first, installable PWA for Android, iOS and desktop.
- Premium black-and-white iOS-inspired UI with clean typography, Lucide icons, rounded cards and minimal animations.
- Mobile bottom navigation and desktop sidebar.
- Secure admin login, password hashing, sessions, CSRF protection and prepared SQL statements.
- Dashboard with Total Clients, Active Clients, Monthly Revenue, Outstanding Amount, Upcoming Payments and Overdue Payments.
- Recent payments and upcoming due date lists.
- Navigation: Dashboard, Clients, Billing, Payments, Receipts, Reports, Settings.
- LKR currency and Asia/Colombo timezone.
- Create proper database architecture and reusable components.

Company branding: **Curtiss**.

Build a production-ready foundation with working authentication and database connectivity. Do not implement other modules yet.




Implement complete Client and Subscription Management modules in the existing Curtiss Client Manager.

Client fields:
- 5-digit sequential Client ID
- Business/Company Name
- Contact Person
- Phone, WhatsApp, Email
- Address
- Purchased Curtiss System
- System URL / Domain
- Installation Date
- Status (Active, Suspended, Inactive)
- Notes

Features:
- Add, edit, view, archive and search clients.
- Detailed client profile with Overview, Subscriptions, Payments, Receipts and Activity tabs.
- Manage multiple subscriptions/services per client.
- Subscription types: Monthly, Yearly, One-Time and Custom.
- Subscription name, amount, billing frequency, start date, next due date and status.
- Automatically calculate upcoming due dates and outstanding amounts.
- Support custom due dates and subscription renewal changes.
- Display overdue, due soon and paid statuses.
- Prevent duplicate billing for the same subscription period.
- Preserve full payment and subscription history.

Follow the existing premium black-and-white UI. Implement fully working database operations and validations.




Implement a complete Billing and Professional PDF Document Generator module.

Company information:
Curtiss
trycurtiss@gmail.com
www.trycurtiss.com
0761407875
79, Dambakanda Estate, Boyagane, Kurunegala

Features:
1. Create invoices/bills for any client.
2. Automatically load client information.
3. Allow unlimited custom line items with:
   - Description
   - Quantity
   - Unit Price (LKR)
   - Line Total (automatically calculated)
4. Add/remove/reorder custom rows.
5. Automatically calculate subtotal, optional discount, optional tax and grand total.
6. Invoice number (5-digit sequential), issue date, due date, payment terms and notes.
7. Save drafts and finalize invoices.
8. Generate professional A4 PDF invoices using a proper PHP PDF library such as Dompdf or TCPDF.
9. Generate PDF documents directly from structured invoice data, NOT browser print, screenshots or webpage captures.
10. Premium black-and-white invoice design with elegant typography, company header, client details, itemized table, totals and footer.
11. Handle multiple pages correctly with repeating table headers, consistent margins and no overlapping content.
12. Download, preview and share the actual PDF file through native sharing where supported, with a download fallback.
13. Maintain saved PDF documents and invoice history.
14. Prevent accidental modification of finalized invoices; use controlled revisions or cancellations.

All monetary calculations must be accurate and performed server-side using decimal-safe arithmetic.

Make the PDF look like a premium commercial invoice suitable for sending directly to clients.





Implement Payment Management and Professional PDF Receipt Generation.

Features:
- Record payments against invoices and clients.
- Support Cash, Bank Transfer, Card and Cheque.
- Record payment date, amount, method, transaction reference and optional payment proof.
- Support partial payments and multiple payments against one invoice.
- Automatically calculate paid amount, outstanding balance and payment status.
- Prevent overpayments and duplicate payment recording.
- Allow custom receipt descriptions and itemized amounts for standalone payments.
- Automatically generate 5-digit sequential receipt numbers.
- Create premium A4 PDF payment receipts using a proper PHP PDF library, not browser print.
- Include Curtiss company information, customer details, payment date, receipt number, payment breakdown, total amount, payment method and reference.
- Clearly distinguish amounts received from amounts still outstanding.
- Download, preview and share actual PDF receipts.
- Show complete payment and receipt history in each client profile.
- Provide cancellation/reversal workflows with audit history rather than deleting recorded transactions.

Maintain accurate financial records and update dashboard totals automatically. Follow the existing UI and architecture.




Implement a public Payment Details page for Curtiss.

Use the uploaded reference image as the main design reference. Recreate its clean black-and-white layout with rounded cards, payment icon, clear typography and individual copy buttons.

Bank details:
Bank: Commercial Bank
Branch: Kurunegala City Office
Account Name: G L DASUN SUSARA SENARATHNE
Account Number: 8018526390

Requirements:
- Public shareable URL, accessible without login.
- Fully responsive, especially for mobile screens.
- One-tap copy buttons for account name and account number.
- Show success feedback after copying.
- Include a Copy All Details option.
- Share Payment Details button using native sharing when available.
- Display Curtiss branding and a security reminder.
- Manage bank account details through admin Settings.
- Support multiple bank accounts in the future.
- Do not expose private client or admin information.
- Follow the uploaded image closely.

Implement a functional, polished payment page integrated with the existing application.




Upgrade the existing subscription and payment tracking modules with complete due date management.

Features:
- Automatically generate recurring billing obligations based on subscription frequency.
- Track next due dates and overdue balances.
- Show Due Today, Due in 7 Days, Overdue and Paid.
- Dashboard calendar and upcoming payments timeline.
- Filter payments by client, date, status and subscription.
- Configurable reminders 7 days, 3 days and 1 day before due date.
- Generate prefilled WhatsApp reminder messages with client name, amount, due date and payment details link.
- Allow manual sending through WhatsApp.
- Record reminder history and prevent duplicate reminders.
- Support partial payments, extensions and adjusted due dates.
- Keep accurate billing history without overwriting previous periods.
- Use reliable server-side date calculations.

Maintain all existing functionality and UI consistency.




Complete the Curtiss Client Manager with reporting, settings, security and performance optimization.

Implement:
- Revenue reports by month, year and custom date range.
- Client payment history and outstanding reports.
- Subscription revenue and renewal analytics.
- PDF and CSV exports.
- Company profile settings for invoices and receipts.
- Bank account management.
- Invoice and receipt numbering settings.
- Admin account and password management.
- Database backup and restore tools with proper access protection.
- Full audit logs for financial changes.
- PWA offline shell and proper update handling.
- Mobile performance and accessibility improvements.
- Comprehensive validation and security testing.

Analyze and test every existing module and workflow. Fix issues without removing features, altering financial records or breaking existing logic.

Ensure the complete application is secure, fast, mobile-friendly and ready for production deployment.