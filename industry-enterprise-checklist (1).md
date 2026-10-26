# Industry, Business & Enterprise Checklist

A checklist of the business modules, industry-specific features, enterprise requirements, and laws/regulations to consider when building a system for a specific business or organization.

This file is used together with:
- `system-development-checklist.md` — the general foundation of every system
- `security-audit-checklist.md` — security measures and attack tests

> **Important:** Not every item is required for every system. Only the sections that match the client's business, size, and industry apply.

> **Laws and regulations change.** The laws listed here are a starting point. Always verify the current version of each law, regulation, or agency rule with the official source (e.g. Official Gazette, BIR, BSP, SEC, DOLE, NPC) before building features based on it.

> **BMPC Project Management status (reviewed 2026-10-25):** [✓] the system needs it · [✗] the system does not need it · ~~struck through~~ already done. BMPC's project system is an internal project, budget, and expense tracker for a cooperative; core cooperative operations (members, shares, loans, savings), accounting books, payroll, and sales are handled by other systems.

---

# Part A — Common Business Modules (Across Industries)

## A1. 💰 Accounting & Finance

- [✗] Chart of accounts
- [✗] Double-entry bookkeeping (debits and credits balance)
- [✗] General ledger
- [✗] Journal entries
- [✗] Accounts receivable
- [✗] Accounts payable
- [✗] Bank accounts and bank reconciliation
- [✗] Cash management / petty cash
- [✗] Fiscal year and accounting periods
- [✗] Period closing (locking closed months)
- [✗] Trial balance
- [✗] Income statement (profit and loss)
- [✗] Balance sheet
- [✗] Cash flow statement
- ~~[✓] Budgeting and budget vs. actual~~
- [✗] Cost centers / departments
- [✗] Fixed assets and depreciation
- [✗] Multi-currency and exchange rates
- [✗] Tax computation (VAT, percentage tax, withholding tax)
- [✗] Accounting standards compliance (PFRS / IFRS)
- ~~[✓] Audit trail for all financial records~~
- ~~[✓] No deletion of posted transactions (reversal entries only)~~

## A2. 🧾 Invoicing, Billing & Receipts

- [✗] Sales invoices
- [✗] Billing statements
- [✗] Receipts / acknowledgment receipts
- [✗] Credit memos / debit memos
- [✗] Sequential, non-reusable invoice numbering
- [✗] Voided invoices kept and marked, not deleted
- [✗] Tax breakdown on invoices (VATable, VAT-exempt, zero-rated, VAT amount)
- [✗] Required invoice information per BIR rules (TIN, business name, address, etc.)
- [✗] Senior citizen discount (20%) and VAT exemption handling
- [✗] PWD discount (20%) and VAT exemption handling
- [✗] Solo parent discount handling, where applicable
- [✗] Discount ID number and name recorded on the transaction
- [✗] Recurring billing
- [✗] Partial payments and installments
- [✗] Payment due dates and overdue tracking
- [✗] Penalties and interest on late payments
- [✗] Statement of account
- [✗] Printed and emailed invoices/receipts
- [✗] BIR registration/accreditation of the system if it issues invoices or receipts
- [✗] E-invoicing / electronic sales reporting if required for the client

## A3. 🤝 Sales & Customer Relationship Management (CRM)

- [✗] Customer records
- [✗] Contacts and companies
- [✗] Leads and prospects
- [✗] Sales pipeline / deal stages
- [✗] Quotations / price proposals
- [✗] Sales orders
- [✗] Customer communication history
- [✗] Follow-up reminders
- [✗] Sales targets and commissions
- [✗] Price lists and customer-specific pricing
- [✗] Customer credit limits
- [✗] Customer segmentation
- [✗] Sales reports

## A4. 📦 Inventory & Stock Management

- [✗] Product/item catalog
- [✗] Units of measure and conversions (piece, box, case)
- [✗] Product variants (size, color)
- [✗] Stock levels per location/warehouse
- [✗] Stock in / stock out
- [✗] Stock transfers between locations
- [✗] Stock adjustments with reasons
- [✗] Physical count / stock taking
- [✗] Reorder points and low-stock alerts
- [✗] Barcode / QR code support
- [✗] Batch / lot tracking
- [✗] Serial number tracking
- [✗] Expiration date tracking
- [✗] First-In-First-Out (FIFO) / First-Expired-First-Out (FEFO)
- [✗] Inventory costing (FIFO, weighted average)
- [✗] Stock movement history (stock card)
- [✗] Damaged, lost, and returned stock handling
- [✗] Negative stock prevention

## A5. 🛒 Procurement & Purchasing

- [✗] Purchase requests / requisitions
- ~~[✓] Approval workflow for purchases~~
- ~~[✓] Supplier/vendor records~~
- [✗] Request for quotation (RFQ)
- ~~[✓] Supplier quotation comparison~~
- [✗] Purchase orders
- [✗] Receiving / delivery receipts
- [✗] Inspection and acceptance
- [✗] Three-way matching (PO, receiving, supplier invoice)
- ~~[✓] Supplier payments~~
- [✗] Supplier performance tracking
- ~~[✓] Budget checking before approval~~

## A6. 👥 Human Resources (HR)

- [✗] Employee records (201 files)
- [✗] Organizational chart, departments, and positions
- [✗] Employment status (probationary, regular, contractual, project-based)
- [✗] Recruitment and applicant tracking
- [✗] Onboarding checklist
- [✗] Employee documents and contracts
- [✗] Leave management (vacation, sick, maternity, paternity, solo parent, service incentive leave)
- [✗] Leave balances and accruals
- [✗] Performance evaluations
- [✗] Training records
- [✗] Disciplinary actions / incident reports
- [✗] Promotions and transfers
- [✗] Offboarding and clearance
- [✗] Certificate of employment generation
- [✗] Employee self-service portal

## A7. 💵 Payroll (Philippines)

- [✗] Salary types (monthly, daily, hourly, piece rate)
- [✗] Minimum wage per region
- [✗] Overtime pay
- [✗] Night differential
- [✗] Holiday pay (regular and special non-working days)
- [✗] Rest day pay
- [✗] Late, undertime, and absence deductions
- [✗] SSS contributions (employee and employer shares)
- [✗] PhilHealth contributions
- [✗] Pag-IBIG contributions
- [✗] Withholding tax on compensation (current TRAIN tax table)
- [✗] De minimis benefits and non-taxable allowances
- [✗] 13th month pay
- [✗] Loans and salary deductions (SSS, Pag-IBIG, company loans)
- [✗] Payslips
- [✗] Payroll register
- [✗] Bank payroll file export
- [✗] Government remittance reports (SSS, PhilHealth, Pag-IBIG)
- [✗] BIR forms (e.g. 1601-C, 2316, alphalist)
- [✗] Final pay computation
- [✗] Payroll locked after approval

## A8. ⏱️ Attendance & Timekeeping

- [✗] Time in / time out
- [✗] Biometric device integration
- [✗] Face recognition or QR attendance
- [✗] GPS/geofenced attendance for field staff
- [✗] Shift schedules and shift swapping
- [✗] Flexible time and compressed work weeks
- [✗] Overtime requests and approvals
- [✗] Official business / field work requests
- [✗] Attendance corrections with approval
- [✗] Daily time record (DTR)

## A9. 📄 Document Management & E-Signatures

- ~~[✓] Document upload and categorization~~
- [✗] Document templates
- ~~[✓] Document versioning~~
- [✗] Document routing and tracking
- [✗] Document approval workflow
- [✗] Incoming/outgoing communications log
- [✗] Electronic signatures
- [✗] Signature verification / QR verification of documents
- [✓] Document retention schedules
- [✗] Records disposal tracking
- [✗] Full-text search of documents

## A10. 🏢 Asset & Property Management

- [✗] Asset registry
- [✗] Asset tagging (property number, QR code)
- [✗] Asset assignment to employees/offices
- [✗] Accountability forms (e.g. property acknowledgment receipts)
- [✗] Asset transfers
- [✗] Maintenance schedules
- [✗] Repair history
- [✗] Depreciation
- [✗] Asset disposal
- [✗] Asset audit / physical inventory

## A11. 🎧 Customer Service & Ticketing

- [✗] Support tickets
- [✗] Ticket categories and priorities
- [✗] Ticket assignment
- [✗] Ticket status tracking
- [✗] Service level targets (response and resolution times)
- [✗] Escalation rules
- [✗] Customer feedback and satisfaction ratings
- [✗] Knowledge base / FAQs
- [✗] Complaint handling

## A12. 📅 Appointments, Booking & Scheduling

- [✗] Available time slots
- [✗] Online booking
- [✗] Booking confirmation
- [✗] Rescheduling and cancellation
- [✗] Cancellation policy and fees
- [✗] No-show tracking
- [✗] Reminders (SMS/email)
- [✗] Staff/resource availability
- [✗] Double-booking prevention
- [✗] Waitlists
- [✗] Deposits / down payments

## A13. 🎟️ Membership, Subscriptions & Loyalty

- [✗] Membership plans
- [✗] Membership cards / digital IDs
- [✗] Subscription renewals
- [✗] Expiration reminders
- [✗] Upgrades and downgrades
- [✗] Loyalty points
- [✗] Rewards redemption
- [✗] Vouchers and promo codes
- [✗] Referral programs

## A14. 📣 Marketing

- [✗] Email campaigns
- [✗] SMS campaigns
- [✗] Opt-in and opt-out (unsubscribe) handling
- [✗] Promotions and campaigns
- [✗] Customer segments
- [✗] Campaign performance tracking
- [✗] Social media integration
- [✗] Landing pages and lead forms

## A15. 📋 Project & Task Management

- ~~[✓] Projects~~
- [✗] Tasks and subtasks
- ~~[✓] Assignees~~
- ~~[✓] Deadlines~~
- ~~[✓] Milestones~~
- ~~[✓] Progress tracking~~
- [✗] Time tracking
- ~~[✓] Project costing and budgets~~
- [✗] Client billing per project

## A16. 📊 Reporting & Business Intelligence

- ~~[✓] Management dashboards~~
- ~~[✓] Key performance indicators (KPIs)~~
- ~~[✓] Daily, weekly, monthly, and annual reports~~
- [✗] Comparisons (month-to-month, year-to-year)
- [✗] Branch/department comparisons
- ~~[✓] Drill-down from summaries to details~~
- [✗] Scheduled reports sent by email
- ~~[✓] Data export for external analysis~~
- [✗] Government/regulatory report formats

## A17. 📑 Contracts Management

- ~~[✓] Contract records (clients, suppliers, employees, leases)~~
- [✗] Contract templates
- [✓] Contract terms, values, and payment schedules
- ~~[✓] Start, end, and renewal dates~~
- [✗] Renewal and expiry reminders
- [✗] Contract amendments
- ~~[✓] Signed copies stored securely~~
- [✗] Contract approval workflow

## A18. 🪪 Permits, Licenses & Compliance Tracking

- [✗] Business permits and licenses
- [✗] Professional licenses of staff (e.g. PRC)
- [✗] Vehicle registrations and insurance
- [✗] Equipment certifications and calibration
- [✗] Expiry dates and renewal reminders
- [✗] Compliance calendar (government filings and deadlines)
- [✗] Inspection records and findings

## A19. 🏢 Facilities & Maintenance Management

- [✗] Buildings, rooms, and facilities
- [✗] Room and facility reservations
- [✗] Maintenance requests
- [✗] Preventive maintenance schedules
- [✗] Work orders and technicians
- [✗] Maintenance costs
- [✗] Utility consumption tracking

## A20. 🚪 Visitor & Access Management

- [✗] Visitor registration
- [✗] Visitor ID/QR passes
- [✗] Host notification when a visitor arrives
- [✗] Visitor logbook
- [✗] Access control integration (doors, gates, turnstiles)
- [✗] Blacklisted/banned visitors
- [✗] Privacy notice for visitors

## A21. 🔢 Queue Management

- [✗] Queue numbers (walk-in, online)
- [✗] Priority lanes (senior citizens, PWD, pregnant women)
- [✗] Queue display screens
- [✗] Counter/window assignment
- [✗] SMS/app notification when the turn is near
- [✗] Waiting time and service time reports

## A22. 📋 Surveys, Feedback & Evaluations

- [✗] Survey/form builder
- [✗] Customer satisfaction surveys
- [✗] Employee/student evaluations
- [✗] Anonymous responses, if needed
- [✗] Rating scales and open comments
- [✗] Results summaries and charts
- [✗] Export of results

## A23. 🗳️ Elections & Voting

- [✗] Voter list and eligibility
- [✗] Candidates and positions
- [✗] One person, one vote enforcement
- [✗] Secret ballot (votes not linked to voters in results)
- [✗] Voting schedule (open and close times)
- [✗] Real-time or post-election tallying
- [✗] Audit trail of the election process
- [✗] Results publication

## A24. 🦺 Occupational Safety & Health

- [✗] Safety incidents and near misses
- [✗] Accident investigation
- [✗] Safety inspections
- [✗] Safety trainings and certifications
- [✗] Personal protective equipment (PPE) issuance
- [✗] Safety officer assignment
- [✗] Government OSH reports, if required

## A25. ✅ Quality Management

- [✗] Quality standards and checklists
- [✗] Inspections and audits
- [✗] Non-conformance reports
- [✗] Corrective and preventive actions (CAPA)
- [✗] Customer complaints linked to quality issues
- [✗] Document control for procedures (SOPs)
- [✗] Quality reports and trends

---

# Part B — Enterprise Requirements (Large Organizations)

## B1. 🔑 Enterprise Identity & Access

- [✗] Single sign-on (SSO) — SAML, OpenID Connect, Microsoft Entra ID, Google Workspace
- [✗] Directory integration (Active Directory / LDAP)
- [✗] Automatic user provisioning and deprovisioning (SCIM)
- [✗] Enforced MFA for all users
- ~~[✓] Fine-grained permissions (per module, per action, per record)~~
- [✗] Delegation of authority (acting/temporary approvers)
- ~~[✓] Separation of duties (the person who creates cannot approve)~~
- [✓] Periodic access reviews
- ~~[✓] Privileged access management for admins~~

## B2. 🏬 Multi-Branch, Multi-Tenant & Multi-Company

- [✗] Multiple branches/locations
- [✗] Data separated per branch, with consolidated head-office view
- [✗] Multi-tenancy (one system serving many client organizations)
- [✗] Strict data isolation between tenants
- [✗] Per-tenant settings, branding, and configuration
- [✗] Multiple companies/legal entities
- [✗] Inter-company transactions
- [✗] Consolidated financial reports
- [✗] Multiple currencies
- [✗] Multiple languages
- [✗] Multiple time zones

## B3. 🟢 Availability & Reliability

- [✗] Uptime target defined (e.g. 99.9%)
- [✗] Service Level Agreement (SLA)
- [✗] No single point of failure
- [✗] Redundant servers / multiple availability zones
- [✗] Database replication
- [✗] Automatic failover
- [✗] Zero-downtime deployments
- ~~[✓] Recovery Time Objective (RTO) defined~~
- ~~[✓] Recovery Point Objective (RPO) defined~~
- [✗] Business continuity plan
- [✗] Disaster recovery site or region
- [✗] Regular disaster recovery drills

## B4. 📈 Scalability & Performance

- ~~[✓] Expected number of users and simultaneous users defined~~
- ~~[✓] Expected data volume and growth defined~~
- [✗] Load testing at expected and peak traffic
- [✗] Horizontal scaling
- [✗] Load balancing
- [✗] Read replicas
- [✗] Database partitioning/sharding, if needed
- [✗] Queue-based processing for heavy work
- ~~[✓] Caching layer~~
- ~~[✓] Performance targets (e.g. page load and API response times)~~

## B5. 🔗 Enterprise Integrations

- [✗] ERP integration (SAP, Oracle, Microsoft Dynamics, Odoo, etc.)
- [✗] Accounting software integration (QuickBooks, Xero, etc.)
- [✗] HRIS integration
- [✗] CRM integration (Salesforce, HubSpot, etc.)
- [✗] Banking integrations (payment files, bank APIs)
- [✗] Government system integrations (BIR, SSS, PhilHealth, Pag-IBIG, PhilGEPS, etc.)
- [✗] Legacy system integration
- ~~[✓] Data exchange formats (CSV, Excel, XML, JSON, EDI)~~
- ~~[✓] Integration error handling and retry~~
- [✗] Integration monitoring
- [✗] Middleware / message queue / event bus, if needed
- [✗] Public API for partners
- [✗] API documentation for integrators

## B6. 🗂️ Data Governance

- ~~[✓] Data ownership defined per module~~
- [✗] Data classification (public, internal, confidential, restricted)
- [✗] Master data management (single source of truth for customers, products, employees)
- ~~[✓] Data quality checks~~
- [✓] Data retention schedules per record type
- [✗] Legal hold (preventing deletion during disputes or investigations)
- [✗] Data residency (where data is allowed to be stored)
- [✗] Data warehouse / reporting database separated from operations
- [✗] Data lineage (where reports get their data)

## B7. 🔄 Change & Release Management

- [✓] Change request process
- [✗] Change approval board / approvers
- [✗] Release schedule
- ~~[✓] Release notes~~
- [✓] Separate development, testing, staging, and production environments
- [✓] User Acceptance Testing sign-off before production
- ~~[✓] Rollback plan for every release~~
- [✗] Maintenance windows communicated in advance
- ~~[✓] Configuration management (tracked settings changes)~~

## B8. 🧑‍💼 Enterprise Support & Operations

- [✗] Help desk / service desk
- [✗] Support tiers (Level 1, 2, 3)
- [✗] Support hours and on-call schedule
- [✓] Incident management process
- [✗] Problem management (finding root causes)
- [✗] Status page for outages
- [✓] User training programs
- [✓] Administrator training
- [✗] Operations runbooks
- ~~[✓] Knowledge transfer documentation~~

## B9. 📑 Procurement & Vendor Requirements (When Selling to Enterprises or Government)

- [✗] Security questionnaires answered
- [✗] Compliance certifications, if required (ISO 27001, SOC 2, etc.)
- [✓] Data processing agreement
- [✓] Non-disclosure agreement
- [✗] Service level agreement
- [✗] Source code escrow, if required
- [✗] Liability and indemnity terms
- [✗] Insurance requirements
- [✗] Business registration documents (DTI/SEC, BIR, Mayor's permit)
- [✗] Government bidding eligibility documents (PhilGEPS registration), if applicable

## B10. 🏛️ Corporate Governance & Internal Controls

- ~~[✓] Internal control requirements~~
- [✗] Approval limits (amount thresholds per role)
- ~~[✓] Segregation of duties~~
- ~~[✓] Complete audit trail (who, what, when, before/after values)~~
- [✗] Internal audit access (read-only auditor role)
- [✗] External auditor reports
- [✗] Risk register
- [✗] Policy acknowledgment by employees
- [✗] Whistleblower / anonymous reporting channel

---

# Part C — Industry-Specific Requirements

## C1. 🏪 Retail & Point of Sale (POS)

- [✗] Fast checkout screen
- [✗] Barcode scanner integration
- [✗] Receipt printer integration (thermal printers)
- [✗] Cash drawer integration
- [✗] Multiple payment methods (cash, card, e-wallet, QR Ph)
- [✗] Split payments
- [✗] Change computation
- [✗] Discounts (senior citizen, PWD, solo parent, promos)
- [✗] Returns, refunds, and exchanges
- [✗] Void transactions with supervisor approval
- [✗] Cashier shifts, cash count, and end-of-day reports
- [✗] X-reading and Z-reading reports
- [✗] Accumulated sales counter that cannot be reset
- [✗] BIR accreditation / Permit to Use for POS machines
- [✗] Offline mode when internet is down
- [✗] Customer display
- [✗] Price checker
- [✗] Multi-store support
- [✗] Product bundles and kits
- [✗] Price overrides only with supervisor approval
- [✗] Weighing scale integration for items sold by weight
- [✗] Layaway / reservation of items
- [✗] Gift cards and store credit
- [✗] Customer accounts and credit purchases
- [✗] Scheduled promos with start and end dates
- [✗] Price change history
- [✗] Sales per cashier, per item, and per hour reports
- [✗] Shrinkage/loss reports
- [✗] E-receipts by SMS or email
- [✗] Shift handover between cashiers

## C2. 🛍️ E-Commerce & Online Marketplace

- [✗] Product listings with images and descriptions
- [✗] Product categories and search
- [✗] Shopping cart
- [✗] Checkout
- [✗] Shipping fee computation
- [✗] Courier integration and tracking
- [✗] Order status tracking
- [✗] Cash on delivery (COD)
- [✗] Online payment gateway
- [✗] Order cancellation and returns/refunds
- [✗] Product reviews and ratings (genuine customers only)
- [✗] Seller accounts and seller dashboards (marketplace)
- [✗] Seller commissions and payouts (marketplace)
- [✗] Seller verification
- [✗] Wishlist
- [✗] Abandoned cart reminders
- [✗] Vouchers and flash sales
- [✗] Clear seller and business information shown (consumer protection / Internet Transactions Act)
- [✗] Terms of sale, return policy, and privacy policy
- [✗] Stock per product variant
- [✗] SEO-friendly product pages
- [✗] Guest checkout
- [✗] Customer address book
- [✗] Shipping zones and rates
- [✗] Pre-orders and back-orders
- [✗] Digital product delivery (downloads, codes)
- [✗] Buyer–seller chat
- [✗] Order fraud checks (e.g. repeated COD refusals)
- [✗] Return merchandise authorization (RMA) workflow
- [✗] Dispute resolution between buyers and sellers
- [✗] Sales tax/invoice per order

## C3. 🍽️ Restaurants & Food Service

- [✗] Menu management
- [✗] Menu modifiers and add-ons
- [✗] Table management and floor plan
- [✗] Order taking (waiter app / tablet)
- [✗] Kitchen display / kitchen order tickets
- [✗] Dine-in, take-out, and delivery orders
- [✗] Food delivery platform integration
- [✗] Bill splitting
- [✗] Service charge handling
- [✗] Recipe and ingredient inventory (bill of materials)
- [✗] Food cost tracking
- [✗] Wastage tracking
- [✗] Reservations
- [✗] Senior citizen/PWD discount per diner
- [✗] QR menu ordering from the table
- [✗] Order notes and special requests sent to the kitchen
- [✗] Kitchen preparation time tracking
- [✗] Combo meals and set menus
- [✗] Happy hour and time-based pricing
- [✗] Void/complimentary items with manager approval
- [✗] Tips handling
- [✗] Delivery rider assignment
- [✗] Sales per menu item (best and worst sellers)
- [✗] Allergen and ingredient information
- [✗] Branch/commissary ordering

## C4. 🏨 Hotels, Resorts & Hospitality

- [✗] Room types and room inventory
- [✗] Room rates and seasonal pricing
- [✗] Reservations and booking engine
- [✗] Online travel agency integration (channel manager)
- [✗] Check-in and check-out
- [✗] Guest records
- [✗] Folio / guest billing
- [✗] Deposits and advance payments
- [✗] Housekeeping status (clean, dirty, out of order)
- [✗] Room service and amenity charges
- [✗] Night audit
- [✗] Event and function room booking
- [✗] Tourism accreditation requirements (DOT), if applicable
- [✗] Rate plans (room only, with breakfast, packages)
- [✗] Overbooking control
- [✗] Group bookings and room blocks
- [✗] Early check-in and late check-out fees
- [✗] Guest ID registration
- [✗] Key card system integration
- [✗] Minibar and incidental charges
- [✗] Lost and found
- [✗] Maintenance/work orders for rooms
- [✗] Guest feedback and reviews
- [✗] Occupancy, average daily rate (ADR), and RevPAR reports

## C5. ✈️ Travel & Tourism

- [✗] Tour packages
- [✗] Itineraries
- [✗] Booking and passenger details
- [✗] Ticketing
- [✗] Visa and travel document checklists
- [✗] Supplier bookings (hotels, transport, guides)
- [✗] Group bookings
- [✗] Payment schedules and installments
- [✗] Cancellation and refund rules
- [✗] Tour guide scheduling
- [✗] Supplier rates and markups
- [✗] Passport/visa expiry checks for travelers
- [✗] Travel insurance add-ons
- [✗] Vouchers and e-tickets
- [✗] Agent/sub-agent commissions
- [✗] Inquiry-to-booking pipeline
- [✗] Payment reminders before travel dates

## C6. 🏦 Banking & Financial Services

- [✗] Customer accounts
- [✗] Know Your Customer (KYC) verification
- [✗] Customer due diligence and risk rating
- [✗] Deposits and withdrawals
- [✗] Fund transfers
- [✗] Interest computation
- [✗] Account statements
- [✗] Double-entry accounting with strict reconciliation
- [✗] Transaction limits
- [✗] Anti-money laundering (AML) monitoring
- [✗] Covered and suspicious transaction reporting (AMLC)
- [✗] Sanctions and watchlist screening
- [✗] Fraud detection
- [✗] Maker-checker (two-person approval)
- [✗] End-of-day processing
- [✗] BSP regulatory reports
- [✗] BSP IT risk management and cybersecurity requirements
- [✗] Financial consumer protection requirements
- [✗] Very strong audit trails and record retention
- [✗] Account opening workflow
- [✗] Dormant account handling
- [✗] Teller cash positions and vault management
- [✗] Cheque deposits and clearing
- [✗] ATM/debit card management
- [✗] Interbank transfer integration (e.g. InstaPay, PESONet)
- [✗] Customer complaints handling and tracking
- [✗] Data retention per regulatory rules

## C7. 💳 Lending, Microfinance & Financing

- [✗] Loan products and terms
- [✗] Loan applications
- [✗] Credit evaluation / scoring
- [✗] Loan approval workflow
- [✗] Loan release / disbursement
- [✗] Amortization schedules
- [✗] Interest computation (diminishing balance, add-on, etc.)
- [✗] Payment collection
- [✗] Penalties for late payments
- [✗] Loan restructuring
- [✗] Collateral tracking
- [✗] Co-makers / guarantors
- [✗] Aging of receivables / past-due reports
- [✗] Full disclosure of loan terms (Truth in Lending)
- [✗] Fair debt collection practices
- [✗] SEC registration and reporting for lending/financing companies, if applicable
- [✗] Loan calculator for customers
- [✗] Required documents checklist per loan type
- [✗] Multiple payment channels
- [✗] Field collection and collection agents
- [✗] Loan write-offs
- [✗] Early payment and rebates
- [✗] Portfolio at risk (PAR) reports
- [✗] Credit information reporting (e.g. CIC), if applicable

## C8. 🤲 Cooperatives

- [✗] Member records
- [✗] Share capital
- [✗] Savings deposits
- [✗] Loans to members
- [✗] Dividends / interest on share capital
- [✗] Patronage refunds
- [✗] General assembly and voting records
- [✗] Board and committee records
- [✗] Cooperative Development Authority (CDA) reports
- [✗] Membership application and pre-membership seminar records
- [✗] Capital build-up tracking
- [✗] Time deposits
- [✗] Cooperative store/consumer operations
- [✗] Computation of members' share of net surplus
- [✗] Audited financial statements and CDA annual reports
- [✗] Election of officers

## C9. 🛡️ Insurance

- [✗] Insurance products and plans
- [✗] Quotations
- [✗] Policy issuance
- [✗] Premium computation and billing
- [✗] Policy renewals
- [✗] Beneficiaries
- [✗] Claims filing
- [✗] Claims evaluation and approval
- [✗] Agent/broker commissions
- [✗] Underwriting rules
- [✗] Insurance Commission requirements
- [✗] Policy endorsements (changes)
- [✗] Cancellations and premium refunds
- [✗] Policy lapse and reinstatement
- [✗] Policy document generation
- [✗] Claims document checklist
- [✗] Claims fraud checks
- [✗] Reinsurance records
- [✗] Agent licensing and expiry

## C10. 📲 E-Wallets, Remittance & Fintech

- [✗] User wallet accounts
- [✗] Cash-in and cash-out
- [✗] Peer-to-peer transfers
- [✗] QR payments (QR Ph)
- [✗] Bill payments
- [✗] Remittance sending and claiming
- [✗] Transaction limits by verification level
- [✗] eKYC (ID and selfie verification)
- [✗] Real-time fraud monitoring
- [✗] Account takeover protection
- [✗] AML monitoring and reporting
- [✗] Ledger that always balances
- [✗] BSP licensing requirements (EMI, remittance, payment operator), if applicable
- [✗] Transaction PIN or biometric confirmation
- [✗] Device binding / trusted devices
- [✗] Transaction reference numbers and receipts
- [✗] Disputes and chargebacks
- [✗] Merchant onboarding
- [✗] Settlement and reconciliation with partners
- [✗] Dormant wallet handling

## C11. 💍 Pawnshops

- [✗] Pawn tickets
- [✗] Item appraisal
- [✗] Item photos and descriptions
- [✗] Loan amount and interest
- [✗] Maturity and expiry dates
- [✗] Renewals and redemptions
- [✗] Auction of unredeemed items
- [✗] Vault inventory
- [✗] BSP pawnshop regulations
- [✗] Partial payments on pawn loans
- [✗] Lost pawn ticket replacement
- [✗] Jewelry karat and weight recording
- [✗] Gadget appraisal
- [✗] Appraisal by authorized appraisers only
- [✗] Insurance of pledged items

## C12. 🏥 Hospitals & Clinics

- [✗] Patient registration
- [✗] Patient records / Electronic Medical Records (EMR)
- [✗] Appointments and queueing
- [✗] Consultations and doctor's notes
- [✗] Vital signs
- [✗] Diagnoses (ICD-10 codes)
- [✗] Prescriptions
- [✗] Laboratory and imaging requests
- [✗] Admission, discharge, and transfer
- [✗] Bed/room management
- [✗] Nursing notes
- [✗] Medication administration records
- [✗] Billing and patient statements
- [✗] PhilHealth membership verification and eClaims
- [✗] HMO accreditation and claims
- [✗] Doctor professional fees
- [✗] Medical certificates
- [✗] Consent forms
- [✗] Strict access control (only the care team sees patient records)
- [✗] Access logging for every patient record view
- [✗] DOH licensing and reporting requirements
- [✗] Health data handled as sensitive personal information under the Data Privacy Act
- [✗] Emergency room triage
- [✗] Operating room scheduling
- [✗] Doctor and nurse schedules
- [✗] Inpatient charges posting
- [✗] Pharmacy and laboratory integration
- [✗] Diet/kitchen orders for patients
- [✗] Discharge summaries
- [✗] Referral system to other facilities
- [✗] Medical records requests and release
- [✗] HMO letters of authorization
- [✗] PhilHealth case rates
- [✗] Infection control records
- [✗] Telemedicine consultations

**Enterprise depth (full hospital information system):**

### C12.1 🏨 Patient Administration & Front Office

- [✗] Master patient index (one record per patient, duplicate detection and merging)
- [✗] Patient ID/hospital number generation
- [✗] Patient wristbands with barcode/QR
- [✗] Next of kin and emergency contacts
- [✗] Patient category (charity, service, private, PhilHealth, HMO, corporate)
- [✗] Admission orders and room/bed assignment
- [✗] Bed transfers and room upgrades
- [✗] Discharge clearance from billing, pharmacy, and nursing
- [✗] Visitor management for wards

### C12.2 🚑 Emergency Department

- [✗] Triage levels and triage notes
- [✗] ER registration for unidentified patients
- [✗] ER tracking board (patient status, waiting time)
- [✗] Doctor assignment and endorsements
- [✗] ER orders (lab, imaging, medications)
- [✗] Disposition (admit, discharge, transfer, home against medical advice)
- [✗] Medico-legal case records
- [✗] Mass casualty / disaster mode
- [✗] Ambulance dispatch and transfer records

### C12.3 🩺 Outpatient Department (OPD)

- [✗] Doctor clinic schedules
- [✗] Online and walk-in appointments
- [✗] OPD queueing
- [✗] Consultation notes (SOAP format)
- [✗] Follow-up scheduling
- [✗] Referrals between specialties
- [✗] Specialty clinic records (OB, pediatrics, IM, surgery, etc.)

### C12.4 🛏️ Inpatient, Nursing & Ward Management

- [✗] Doctor's orders entry (computerized provider order entry)
- [✗] Order verification by nurses and pharmacists
- [✗] Nursing care plans
- [✗] Vital signs charting and early warning scores
- [✗] Intake and output monitoring
- [✗] Medication administration record (MAR)
- [✗] Barcode medication administration (scan patient and medicine)
- [✗] Nursing shift endorsements/handover
- [✗] Nurse staffing and shift schedules
- [✗] Patient diet orders
- [✗] Fall risk, pressure ulcer, and other risk assessments
- [✗] ICU flowsheets and monitoring, if applicable
- [✗] Newborn and maternity records (labor monitoring, delivery records)

### C12.5 🔪 Surgery & Operating Room

- [✗] OR scheduling and room assignment
- [✗] Surgical team assignment
- [✗] Pre-operative checklists and clearances
- [✗] Surgical safety checklist
- [✗] Anesthesia records
- [✗] Operative reports
- [✗] Instrument and sponge counts
- [✗] Recovery room monitoring
- [✗] Surgical supplies and implants used per case

### C12.6 💊 Hospital Pharmacy

- [✗] Formulary management
- [✗] Inpatient medicine dispensing per order
- [✗] Unit-dose dispensing
- [✗] Ward stock and floor stock
- [✗] Drug interaction, allergy, and dose checking
- [✗] Controlled/dangerous drugs register
- [✗] Returns of unused medicines
- [✗] Antimicrobial stewardship monitoring
- [✗] Pharmacy charges posted to patient accounts

### C12.7 🔬 Laboratory, Radiology & Diagnostics

- [✗] Laboratory Information System (LIS) integration
- [✗] Specimen barcode labels
- [✗] Lab machine interfacing
- [✗] Critical value notifications
- [✗] Radiology Information System (RIS)
- [✗] Imaging requests and scheduling (X-ray, ultrasound, CT, MRI)
- [✗] PACS integration for medical images (DICOM)
- [✗] Radiologist reports
- [✗] Results viewable by the requesting doctor

### C12.8 🧾 Billing, Claims & Finance

- [✗] Charges posted automatically from services, pharmacy, lab, and supplies
- [✗] Room and board charges per day
- [✗] Professional fees per doctor
- [✗] Package rates
- [✗] PhilHealth benefit deductions and case rates
- [✗] PhilHealth eClaims submission and status tracking
- [✗] HMO approvals, LOAs, and claims
- [✗] Senior citizen/PWD discounts
- [✗] Promissory notes and patient balances
- [✗] Charity/social service discounts and approvals
- [✗] Statement of account and final billing
- [✗] Claims denials and resubmissions
- [✗] Doctors' professional fee payouts

### C12.9 📁 Health Information Management (Medical Records)

- [✗] Complete electronic medical record per patient
- [✗] Diagnosis and procedure coding (ICD-10, procedure codes)
- [✗] Medical records completion tracking (incomplete charts)
- [✗] Release of medical records with authorization
- [✗] Medical certificates and abstracts
- [✗] Birth and death certificate preparation
- [✗] Records retention schedules
- [✗] Disease registries and statistics

### C12.10 🏭 Hospital Operations & Support Services

- [✗] Central supply and sterilization (CSSD) tracking
- [✗] Medical supplies inventory per department
- [✗] Purchasing and supplier management
- [✗] Biomedical equipment inventory and maintenance
- [✗] Equipment calibration records
- [✗] Linen and laundry management
- [✗] Dietary/kitchen management
- [✗] Housekeeping for rooms and beds
- [✗] Waste management (infectious and hazardous waste)

### C12.11 🛡️ Quality, Patient Safety & Infection Control

- [✗] Incident and adverse event reporting
- [✗] Medication error reporting
- [✗] Infection surveillance (hospital-acquired infections)
- [✗] Isolation tracking
- [✗] Patient satisfaction surveys
- [✗] Clinical quality indicators
- [✗] Accreditation requirements (e.g. PhilHealth, DOH, international accreditation)

### C12.12 🔗 Interoperability, Portals & Enterprise IT

- [✗] HL7/FHIR interfaces between systems
- [✗] Integration with national health systems and reporting, as required
- [✗] Doctor portal (orders, results, patient list)
- [✗] Patient portal (appointments, results, bills)
- [✗] Telemedicine module
- [✗] Clinical decision support (alerts, reminders)
- [✗] Downtime procedures and paper backup forms
- [✗] High availability (hospital systems run 24/7)
- [✗] Strict role-based access per department and patient relationship
- [✗] "Break-glass" emergency access with logging
- [✗] Full access audit logs for every patient record view

## C13. 🏫 School Clinics & Workplace Health

- [✗] Student/employee health records
- [✗] Clinic visits and complaints
- [✗] Medicine dispensing and inventory
- [✗] Medical clearances
- [✗] Annual physical exam records
- [✗] Vaccination records
- [✗] Referrals to hospitals
- [✗] Parent/guardian notifications (for minors)
- [✗] Strict confidentiality of health records
- [✗] Dental records
- [✗] Health screening (height, weight, BMI, vision)
- [✗] Injury/incident reports
- [✗] First aid supplies inventory
- [✗] Referral to guidance office or counselors

## C14. 💊 Pharmacies & Drugstores

- [✗] Medicine catalog (generic and brand names)
- [✗] Prescription recording
- [✗] Prescription-only medicine checks
- [✗] Dangerous drugs records and special prescriptions, if applicable
- [✗] Batch and expiration tracking
- [✗] FEFO stock rotation
- [✗] Near-expiry alerts
- [✗] Senior citizen/PWD medicine discounts and purchase booklets
- [✗] FDA licensing requirements
- [✗] Generic substitution records
- [✗] Drug interaction warnings
- [✗] Prescription refills
- [✗] Price per piece, strip, or box
- [✗] Purchases from distributors
- [✗] Returns to suppliers
- [✗] Online orders and delivery

## C15. 🔬 Laboratories & Diagnostic Centers

- [✗] Test catalog and price list
- [✗] Test requests
- [✗] Specimen collection and labeling
- [✗] Specimen tracking
- [✗] Result entry
- [✗] Reference ranges and abnormal flags
- [✗] Result validation by authorized personnel
- [✗] Result release (printed and online)
- [✗] Laboratory machine integration
- [✗] Quality control records
- [✗] Test packages and promos
- [✗] HMO billing
- [✗] Laboratory information system (LIS) machine interfacing
- [✗] Critical value alerts to doctors
- [✗] Turnaround time tracking
- [✗] Sample rejection and recollection
- [✗] Reagent and supplies inventory

## C16. 🦷 Dental Clinics

- [✗] Patient records
- [✗] Dental chart (tooth chart)
- [✗] Treatment plans
- [✗] Procedures performed per tooth
- [✗] X-ray image storage
- [✗] Appointments
- [✗] Installment payments for treatments
- [✗] Consent forms
- [✗] Periodontal charting
- [✗] Dental laboratory work orders (dentures, crowns)
- [✗] Recall reminders (cleaning every 6 months)
- [✗] Procedure codes and price list
- [✗] Before-and-after photos

## C17. 🐾 Veterinary Clinics

- [✗] Pet owner records
- [✗] Pet records (species, breed, age)
- [✗] Vaccination schedules and reminders
- [✗] Consultations and treatments
- [✗] Prescriptions
- [✗] Grooming and boarding services
- [✗] Billing
- [✗] Pet weight history
- [✗] Surgery records
- [✗] Laboratory results
- [✗] Microchip numbers
- [✗] Boarding check-in and check-out
- [✗] Deworming and vaccination reminders
- [✗] Pet medicine inventory

## C18. 🎓 Schools & Universities

- [✗] Student records
- [✗] Admission and application
- [✗] Enrollment
- [✗] Sections and class schedules
- [✗] Subjects/courses and curriculum
- [✗] Prerequisites
- [✗] Teacher/faculty loading
- [✗] Room scheduling
- [✗] Grading system
- [✗] Grade encoding and approval
- [✗] Report cards / transcript of records
- [✗] Attendance
- [✗] Tuition and fees assessment
- [✗] Scholarships and discounts
- [✗] Student accounts and payments
- [✗] Clearances
- [✗] Graduation requirements and evaluation
- [✗] Certificates and diplomas
- [✗] Parent portal
- [✗] Learning management system
- [✗] Library integration
- [✗] Alumni records
- [✗] DepEd, CHED, or TESDA reporting formats, as applicable
- [✗] Learner Reference Number (LRN) handling, if basic education
- [✗] Extra protection for minors' data
- [✗] Child protection policies
- [✗] Entrance exams and results
- [✗] Student ID generation
- [✗] Class records for teachers
- [✗] Adding, dropping, and withdrawing subjects
- [✗] Incomplete/failed grade handling
- [✗] Shifting and transferring programs
- [✗] Cross-enrollment
- [✗] Enrollment slots per section
- [✗] Academic calendar
- [✗] Guidance and counseling records
- [✗] Discipline records
- [✗] Student organizations and events
- [✗] OJT/internship tracking
- [✗] Faculty evaluation by students
- [✗] Accreditation reports
- [✗] Online tuition payments

## C19. 📚 Training Centers & Online Learning

- [✗] Courses and modules
- [✗] Lessons (video, documents)
- [✗] Quizzes and exams
- [✗] Question banks
- [✗] Exam randomization and timers
- [✗] Anti-cheating measures
- [✗] Progress tracking
- [✗] Certificates of completion
- [✗] Instructor management
- [✗] Course enrollment and payment
- [✗] Discussion forums
- [✗] Cohorts/batches
- [✗] Live class integration (video meetings)
- [✗] Assignments and submissions
- [✗] Grading rubrics
- [✗] Badges and achievements
- [✗] Course completion requirements
- [✗] Instructor payouts

## C20. 📖 Libraries

- [✗] Book catalog
- [✗] Barcode/RFID for books
- [✗] Borrowing and returns
- [✗] Due dates and overdue fines
- [✗] Reservations
- [✗] Member/patron records
- [✗] Digital resources
- [✗] Inventory and weeding
- [✗] Online public access catalog (OPAC)
- [✗] Holds and reservations
- [✗] Inter-library loans
- [✗] E-books and digital subscriptions
- [✗] Visitor logbook
- [✗] Acquisitions and book suppliers
- [✗] Book condition and repairs

## C21. 🏛️ Government Offices & Agencies

- [✗] Citizen/client services and online applications
- [✗] Document tracking system
- [✗] Citizen's Charter processing times (Ease of Doing Business / Anti-Red Tape Act)
- [✗] Queueing system
- [✗] Permits and licenses
- [✗] Official forms in the prescribed formats
- [✗] Signatories and approval chains
- [✗] Procurement following the government procurement law and PhilGEPS
- [✗] Annual Procurement Plan and Project Procurement Management Plan
- [✗] Supply and property management forms (e.g. RIS, PAR, ICS)
- [✗] Budget, obligations, and disbursements tracking
- [✗] COA audit requirements
- [✗] Records management following National Archives rules
- [✗] Freedom of Information requests, if applicable
- [✗] Accessibility for persons with disabilities
- [✗] Data Privacy Act compliance (government also handles large amounts of personal data)
- [✗] Data sharing agreements with other agencies
- [✗] Online appointments
- [✗] Online payment of fees
- [✗] Client satisfaction measurement (per Citizen's Charter)
- [✗] Complaints and feedback handling
- [✗] Government HR (plantilla positions, Personal Data Sheet, leave cards)
- [✗] Travel orders and authority to travel
- [✗] Memoranda, orders, and issuances
- [✗] Project/Program budget tracking
- [✗] Transparency seal and public disclosure documents

## C22. 🏘️ Local Government Units (LGU) & Barangays

- [✗] Resident/household profiling
- [✗] Barangay clearance and certificates
- [✗] Business permits and licensing
- [✗] Real property tax
- [✗] Community tax certificates (cedula)
- [✗] Blotter records
- [✗] Health center records
- [✗] Disaster risk reduction (evacuation, relief distribution)
- [✗] Social services (senior citizens, PWD, solo parents, 4Ps beneficiaries)
- [✗] Ordinances and resolutions
- [✗] Projects and infrastructure tracking
- [✗] Katarungang Pambarangay (barangay justice) case records
- [✗] Barangay ID
- [✗] Certificates of indigency and residency
- [✗] Senior citizen and PWD ID issuance
- [✗] Scholarship programs
- [✗] Civil registry (birth, marriage, death records)
- [✗] Building, zoning, and occupancy permits
- [✗] Business tax assessment
- [✗] Tax mapping

## C23. 🏠 Real Estate & Property Management

- [✗] Property listings
- [✗] Units/lots inventory
- [✗] Reservations and reservation fees
- [✗] Sales contracts
- [✗] Payment schedules and amortizations
- [✗] Buyer protections for installment sales (Maceda Law)
- [✗] Title and document tracking
- [✗] Broker/agent commissions
- [✗] Licensed real estate practitioner requirements
- [✗] Tenant management
- [✗] Lease contracts
- [✗] Rent billing
- [✗] Association dues
- [✗] Maintenance requests
- [✗] Lead management and site viewing schedules
- [✗] Property photos and virtual tours
- [✗] Price list per unit with discounts
- [✗] Bank and Pag-IBIG financing tracking
- [✗] Unit turnover and punch lists
- [✗] Warranty and repair claims after turnover

## C24. 🛏️ Dormitories, Boarding Houses & Rentals

- [✗] Rooms and bed spaces
- [✗] Room availability
- [✗] Tenant/boarder registration
- [✗] Tenant documents and IDs
- [✗] Emergency contacts
- [✗] Guardian/parent contacts (for student boarders)
- [✗] Monthly rent billing
- [✗] Utility billing (electricity, water submetering)
- [✗] Deposits and advance payments
- [✗] Payment tracking and overdue alerts
- [✗] House rules and acknowledgment
- [✗] Curfew and visitor logs
- [✗] Maintenance requests
- [✗] Move-in and move-out inspections
- [✗] Rent increase limits, if covered by rent control rules
- [✗] Room assignment rules (e.g. by gender or year level)
- [✗] Room transfers
- [✗] Furniture and appliance inventory per room
- [✗] Rule violations and sanctions
- [✗] Online applications and waitlists
- [✗] Contract renewals
- [✗] Notices to parents/guardians

## C25. 🏗️ Construction

- ~~[✓] Projects and sites~~
- [✗] Bill of quantities
- [✗] Cost estimates
- ~~[✓] Project schedules (Gantt charts)~~
- [✓] Progress billing
- [✓] Retention money
- [✗] Change orders
- [✗] Materials tracking per site
- [✗] Equipment tracking
- ~~[✓] Subcontractors~~
- ~~[✓] Daily site reports~~
- [✗] Safety reports and incidents
- [✗] Manpower and daily labor payroll
- ~~[✓] Contractor license requirements (PCAB), if applicable~~
- ~~[✓] Budget vs. actual cost per project~~
- [✗] Purchase requests per site
- [✗] Equipment rentals
- ~~[✓] Site photo timeline~~
- ~~[✓] Weather and other delay logs~~
- [✗] Building permits
- ~~[✓] Progress percentage per work item (S-curve)~~
- [✗] Punch list and project turnover

## C26. 🏭 Manufacturing

- [✗] Bill of materials (BOM)
- [✗] Production planning
- [✗] Work orders / job orders
- [✗] Raw materials inventory
- [✗] Work-in-progress tracking
- [✗] Finished goods inventory
- [✗] Production costing
- [✗] Machine scheduling
- [✗] Machine maintenance
- [✗] Quality control and inspection
- [✗] Defects and rework
- [✗] Batch/lot traceability
- [✗] Production reports and yield
- [✗] Machine/IoT integration, if needed
- [✗] Workplace safety records
- [✗] Material requirements planning (MRP)
- [✗] Production lines
- [✗] Machine downtime tracking
- [✗] Overall equipment effectiveness (OEE)
- [✗] Scrap and waste records
- [✗] Packaging and labeling
- [✗] Certificates of analysis
- [✗] Product recalls

## C27. 🚚 Warehousing, Logistics & Courier

- [✗] Warehouse locations (zones, racks, bins)
- [✗] Receiving
- [✗] Put-away
- [✗] Picking and packing
- [✗] Dispatch
- [✗] Shipment creation
- [✗] Waybills and labels
- [✗] Shipment tracking
- [✗] Route planning
- [✗] Rider/driver assignment
- [✗] Proof of delivery (photo, signature)
- [✗] Failed delivery and return-to-sender handling
- [✗] COD remittance tracking
- [✗] Delivery rates by area and weight
- [✗] Bulk shipment upload
- [✗] Branch/hub transfers and sorting
- [✗] Delivery manifests
- [✗] COD reconciliation per rider and branch
- [✗] Rider earnings
- [✗] Delivery time targets
- [✗] Damage and loss claims
- [✗] API for online sellers

## C28. 🚌 Transportation & Fleet Management

- [✗] Vehicle records
- [✗] Driver records and licenses
- [✗] Trip scheduling
- [✗] GPS tracking
- [✗] Fuel tracking
- [✗] Maintenance schedules
- [✗] Vehicle registration and insurance expiry alerts
- [✗] Driver violations and incidents
- [✗] Fare computation
- [✗] Ticketing and seat reservations
- [✗] Franchise and regulatory requirements (LTFRB, LTO), if applicable
- [✗] Driver shifts
- [✗] Trip logs
- [✗] Toll/RFID expenses
- [✗] Boundary collection (for jeepneys, taxis, tricycles)
- [✗] Passenger counts
- [✗] Vehicle inspection checklists
- [✗] Accident reports
- [✗] Fuel efficiency reports

## C29. 🌾 Agriculture, Fisheries & Aquaculture

### C29.1 Common Agricultural Features

- [✗] Farmer, fisherfolk, and producer records
- [✗] Registration numbers from government registries (e.g. RSBSA, FishR, BoatR), if applicable
- [✗] Farm, plot, pond, or site records
- [✗] Map/GPS location of farms and sites
- [✗] Area size and boundaries
- [✗] Production cycles (start, growing, harvest)
- [✗] Production calendar and schedules
- [✗] Inputs tracking (seeds, seedlings, fertilizer, feeds, chemicals)
- [✗] Input costs and production cost per cycle
- [✗] Harvest records and yields
- [✗] Yield per area / per cycle reports
- [✗] Losses and damage records (pests, disease, weather, disasters)
- [✗] Weather data integration and alerts
- [✗] Offline data collection in the field with later sync
- [✗] Photo capture with date, time, and GPS
- [✗] Simple interface for low-tech users, in the local language
- [✗] Works on low-end phones and weak signal
- [✗] Buyer/trader records and selling prices
- [✗] Market price monitoring
- [✗] Income and profit per cycle
- [✗] Association/cooperative membership
- [✗] Crop/fishery insurance records and claims (e.g. PCIC), if applicable
- [✗] Government programs, subsidies, and beneficiaries
- [✗] DA, BFAR, or LGU agriculture office reporting formats

### C29.2 🌱 Crop Farming

- [✗] Crop types and varieties
- [✗] Planting date and expected harvest date
- [✗] Land preparation activities
- [✗] Fertilizer and pesticide application logs (type, amount, date)
- [✗] Irrigation schedules and water usage
- [✗] Soil test results
- [✗] Growth stage monitoring
- [✗] Pest and disease monitoring
- [✗] Image-based pest/disease detection, if applicable
- [✗] Recommended actions per pest/disease
- [✗] Farm labor and activity logs
- [✗] Farm equipment and machinery usage
- [✗] Crop rotation history
- [✗] Organic certification records, if applicable
- [✗] Good Agricultural Practices (GAP) records, if applicable

### C29.3 🐄 Livestock & Poultry

- [✗] Animal records (ID/tag, species, breed, sex, birth date)
- [✗] Herd/flock records and headcount
- [✗] Animal movement and location
- [✗] Breeding and insemination records
- [✗] Pregnancy, birth, and hatching records
- [✗] Feeding schedules and feed consumption
- [✗] Weight and growth monitoring
- [✗] Vaccination and medication schedules
- [✗] Withdrawal periods before sale/slaughter
- [✗] Disease outbreaks and quarantine
- [✗] Mortality records
- [✗] Egg or milk production records
- [✗] Animal sales and purchases
- [✗] Slaughter and meat inspection records, if applicable
- [✗] Animal transport permits, if applicable
- [✗] Biosecurity checklists
- [✗] Animal welfare compliance

### C29.4 🎣 Capture Fisheries

- [✗] Fisherfolk records
- [✗] Boat/vessel records and registration
- [✗] Fishing gear records
- [✗] Fishing trips (departure, return, area)
- [✗] Catch records (species, quantity, weight)
- [✗] Landing site records
- [✗] Fishing zone and closed season rules
- [✗] Protected species and size limits
- [✗] Fish port / landing fees
- [✗] Sales to buyers and traders
- [✗] Safety at sea (trip tracking, emergency contacts)
- [✗] Municipal fisheries ordinances and permits

### C29.5 🐟 Aquaculture (Fish Ponds, Cages, Pens & Hatcheries)

- [✗] Pond, cage, pen, or tank records
- [✗] Species and stock source (hatchery, wild)
- [✗] Stocking date, quantity, and density
- [✗] Feeding schedules and feed consumption
- [✗] Feed conversion ratio (FCR)
- [✗] Water quality monitoring (temperature, pH, dissolved oxygen, salinity, ammonia)
- [✗] Water quality alerts when values are unsafe
- [✗] Sampling for growth and weight
- [✗] Survival rate and mortality records
- [✗] Disease monitoring and treatments
- [✗] Harvest records (partial and full harvest)
- [✗] Hatchery records (broodstock, spawning, fry/fingerlings)
- [✗] Pond preparation and maintenance logs
- [✗] IoT sensor integration, if applicable
- [✗] Production cost and profit per cycle
- [✗] Fishpond lease or permit records, if applicable

### C29.6 🌿 Seaweed Farming

- [✗] Seaweed farmer records
- [✗] Farm site location and area
- [✗] Farming method (fixed off-bottom, floating, longline, etc.)
- [✗] Seaweed species and strains
- [✗] Seedling/propagule source
- [✗] Planting date and number of lines
- [✗] Growth monitoring (weight, length, daily growth rate)
- [✗] Water conditions (temperature, salinity, clarity)
- [✗] Disease monitoring (e.g. ice-ice) and pest/epiphyte monitoring
- [✗] Image-based disease identification, if applicable
- [✗] Recommended actions per disease
- [✗] Harvest date and fresh weight
- [✗] Drying records and dried weight
- [✗] Quality grading
- [✗] Moisture content
- [✗] Buyers, traders, and selling prices
- [✗] Losses from typhoons and weather events
- [✗] Production reports per farmer, site, and season
- [✗] BFAR and LGU reporting formats, if applicable

### C29.7 🌳 Forestry & Agroforestry

- [✗] Tree inventory (species, location, age, size)
- [✗] Planting and reforestation records
- [✗] Nursery and seedling stock
- [✗] Survival monitoring
- [✗] Harvesting and cutting permits
- [✗] Timber/product transport permits
- [✗] Protected areas and boundaries
- [✗] Forest fire and illegal logging reports
- [✗] DENR reporting, if applicable

### C29.8 📦 Post-Harvest, Processing, Cold Chain & Agri-Trading

- [✗] Receiving and weighing of produce
- [✗] Quality grading and sorting
- [✗] Drying, milling, and processing records (e.g. rice milling)
- [✗] Processing yields and losses
- [✗] Batch/lot traceability from farm to buyer
- [✗] Cold storage temperature monitoring
- [✗] Cold chain logs during transport
- [✗] Storage/warehouse inventory
- [✗] Packaging and labeling
- [✗] Buying stations and trader transactions
- [✗] Price per grade/kilo
- [✗] Contract farming agreements
- [✗] Delivery to buyers and markets
- [✗] Food safety records
- [✗] Export documents, if applicable

### C29.9 🧑‍🌾 Agricultural Extension & Government Programs

- [✗] Extension worker/technician records
- [✗] Farm visits and technical assistance logs
- [✗] Trainings and seminars attended by farmers
- [✗] Distribution of seeds, fertilizer, fingerlings, equipment, or other aid
- [✗] Beneficiary eligibility and duplicate-beneficiary prevention
- [✗] Proof of distribution (signature, photo)
- [✗] Program monitoring and evaluation
- [✗] Disaster damage assessment for farms and fisheries
- [✗] Reports to municipal, provincial, and national agencies

### C29.10 🛰️ Precision Agriculture, IoT & Remote Sensing

- [✗] Sensor integration (soil moisture, water quality, weather stations)
- [✗] Real-time sensor dashboards
- [✗] Threshold alerts (SMS/push)
- [✗] Satellite or drone imagery
- [✗] Vegetation indices (e.g. NDVI) and crop health maps
- [✗] GIS maps and layers
- [✗] Machine learning models for detection, classification, or yield prediction
- [✗] Model accuracy monitoring and retraining
- [✗] Offline/edge processing where internet is weak
- [✗] Sensor calibration and maintenance records

## C30. ⚡ Utilities (Water, Electricity, Internet)

- [✗] Customer accounts
- [✗] Service connection applications
- [✗] Meter records
- [✗] Meter reading (mobile, offline)
- [✗] Consumption computation
- [✗] Tiered/rate-based billing
- [✗] Bill printing and delivery
- [✗] Payments and collection
- [✗] Disconnection and reconnection
- [✗] Service interruption notices
- [✗] Complaints and repair requests
- [✗] Regulatory reporting (e.g. ERC, LWUA, NTC), if applicable
- [✗] Meter replacements
- [✗] Bill adjustments
- [✗] Senior citizen discounts on utility bills, where applicable
- [✗] Payment center and online payment integration
- [✗] Abnormal consumption alerts
- [✗] Leak and outage reports
- [✗] Customer portal

## C31. 📡 Telecommunications & Internet Service Providers

- [✗] Subscriber accounts
- [✗] Plans and packages
- [✗] Installation scheduling
- [✗] Recurring billing
- [✗] Network/device provisioning
- [✗] Usage tracking
- [✗] Outage tracking
- [✗] Technical support tickets
- [✗] SIM registration requirements, if applicable
- [✗] NTC regulatory requirements
- [✗] Plan upgrades and downgrades
- [✗] Prorated billing
- [✗] Bandwidth/usage monitoring
- [✗] Modem and equipment tracking
- [✗] Technician dispatch
- [✗] Service suspension for non-payment
- [✗] Customer churn reports

## C32. 🎭 Events, Ticketing & Entertainment

- [✗] Events and schedules
- [✗] Venue and seating maps
- [✗] Ticket types and pricing
- [✗] Online ticket sales
- [✗] QR code tickets
- [✗] Ticket scanning at entry
- [✗] Duplicate ticket prevention
- [✗] Refunds and transfers
- [✗] Attendee lists
- [✗] Event registration forms
- [✗] Early-bird pricing
- [✗] Seat holds during checkout
- [✗] Group tickets
- [✗] Complimentary tickets
- [✗] Sponsors and booths
- [✗] Check-in reports
- [✗] Virtual events/livestream access

## C33. 🏋️ Gyms, Fitness & Sports

- [✗] Memberships and plans
- [✗] Check-in (QR, RFID, face)
- [✗] Class schedules and booking
- [✗] Trainer assignments
- [✗] Personal training sessions
- [✗] Member progress tracking
- [✗] Facility/court reservations
- [✗] Waivers and health declarations
- [✗] Membership freeze
- [✗] Body measurements and progress photos
- [✗] Workout programs
- [✗] Locker rentals
- [✗] Guest passes
- [✗] Equipment maintenance

## C34. 💇 Salons, Spas & Personal Services

- [✗] Services and price list
- [✗] Appointments
- [✗] Staff schedules
- [✗] Staff commissions
- [✗] Customer history and preferences
- [✗] Packages and service bundles
- [✗] Product sales
- [✗] Gift certificates
- [✗] Service duration and chair/room availability
- [✗] Walk-in queue
- [✗] Products used per service
- [✗] Staff ratings

## C35. 🚗 Automotive (Repair Shops & Dealerships)

- [✗] Customer and vehicle records
- [✗] Job orders
- [✗] Vehicle inspection checklists
- [✗] Parts inventory
- [✗] Labor charges
- [✗] Repair estimates and approvals
- [✗] Service history per vehicle
- [✗] Preventive maintenance reminders
- [✗] Vehicle sales and financing (dealerships)
- [✗] Warranty claims
- [✗] Parts ordering
- [✗] Technician labor time
- [✗] Insurance claims processing
- [✗] Vehicle check-in photos
- [✗] Test drives
- [✗] Trade-ins
- [✗] Registration and insurance renewal assistance

## C36. ⚖️ Legal Services & Law Firms

- [✗] Client records
- [✗] Case/matter management
- [✗] Court hearing schedules
- [✗] Deadlines and reminders
- [✗] Document management
- [✗] Time tracking and billable hours
- [✗] Client billing and retainers
- [✗] Conflict-of-interest checks
- [✗] Strict confidentiality and access control
- [✗] Court documents and pleadings
- [✗] Client trust funds
- [✗] Notarial register
- [✗] Document templates
- [✗] Prescriptive period/deadline reminders

## C37. 🧮 Accounting, Bookkeeping & Consulting Firms

- [✗] Client records
- [✗] Engagements/projects
- [✗] Tax filing deadlines and reminders
- [✗] Document collection from clients
- [✗] Time tracking
- [✗] Billing and retainers
- [✗] Working papers
- [✗] Client portal
- [✗] BIR form preparation and filing tracking
- [✗] Client compliance calendar
- [✗] Staff assignment per client
- [✗] Retainer agreements

## C38. 🎧 BPO & Call Centers

- [✗] Agent records
- [✗] Shift scheduling and workforce management
- [✗] Call/ticket logging
- [✗] Call recording storage, if applicable
- [✗] Quality assurance scoring
- [✗] KPIs (handling time, resolution rate, satisfaction)
- [✗] Client account separation
- [✗] Strict data protection for client customer data
- [✗] Client security and compliance requirements (often international standards)
- [✗] Schedule adherence
- [✗] Occupancy and utilization
- [✗] Coaching logs
- [✗] Client-specific training
- [✗] NDA tracking
- [✗] Clean desk compliance

## C39. 🧑‍🔧 Staffing, Recruitment & Manpower Agencies

- [✗] Applicant database
- [✗] Job orders from clients
- [✗] Applicant screening and interviews
- [✗] Deployment to clients
- [✗] Contracts
- [✗] Billing to clients
- [✗] Payroll of deployed workers
- [✗] DOLE requirements for contractors/subcontractors, if applicable
- [✗] Overseas recruitment requirements (DMW), if applicable
- [✗] Skills matching to job orders
- [✗] Pre-employment requirements tracking
- [✗] Deployment history
- [✗] End-of-contract tracking
- [✗] Client feedback on workers

## C40. 💂 Security Agencies

- [✗] Guard records and licenses
- [✗] Guard deployment to posts
- [✗] Shift schedules
- [✗] Guard tour/patrol checkpoints
- [✗] Incident reports
- [✗] Visitor logs
- [✗] Firearm records, if applicable
- [✗] License expiry alerts
- [✗] Guard training records
- [✗] Post orders/instructions
- [✗] Uniform and equipment issuance
- [✗] Payroll per post
- [✗] Client billing per guard and per hour

## C41. 🧺 Laundry, Repair, Printing & Small Services

- [✗] Job orders / claim stubs
- [✗] Service pricing (per kilo, per item, per page)
- [✗] Job status tracking
- [✗] Ready-for-pickup notifications
- [✗] Payments and balances
- [✗] Unclaimed items tracking
- [✗] Weigh-in and item tagging
- [✗] Garment care notes
- [✗] Pickup and delivery
- [✗] Damage or lost item claims

## C42. ⛪ Non-Profits, NGOs & Religious Organizations

- [✗] Member records
- [✗] Donor records
- [✗] Donations and pledges
- [✗] Donation receipts
- [✗] Fund accounting (restricted vs. unrestricted funds)
- [✗] Programs and beneficiaries
- [✗] Volunteer management
- [✗] Events and activities
- [✗] Grant tracking and reporting
- [✗] Sacramental or ministry records, if religious
- [✗] Transparency reports
- [✗] Donor portal
- [✗] Online and recurring donations
- [✗] Donation acknowledgment receipts
- [✗] Project budgets
- [✗] Beneficiary case records
- [✗] Impact reports

## C43. ⚱️ Funeral Services & Memorial Parks

- [✗] Service packages
- [✗] Contracts
- [✗] Plan holders and installment payments
- [✗] Lot/niche inventory and mapping
- [✗] Interment records
- [✗] Death certificate and document tracking
- [✗] Chapel booking
- [✗] Hearse scheduling
- [✗] Casket and urn inventory
- [✗] Memorial plan maturity and claims

## C44. 🅿️ Parking & Facility Access

- [✗] Vehicle entry and exit logging
- [✗] Plate number recognition, if applicable
- [✗] Parking fee computation
- [✗] Monthly parking passes
- [✗] Slot availability
- [✗] Lost ticket handling
- [✗] RFID/QR access
- [✗] Overnight and flat rates
- [✗] Parking violations
- [✗] Reserved slots

## C45. ⛏️ Mining, Energy & Heavy Industry

- [✗] Equipment and heavy machinery tracking
- [✗] Production output tracking
- [✗] Environmental monitoring and compliance reports
- [✗] Health and safety incidents
- [✗] Permits and license tracking
- [✗] Shift and crew management
- [✗] Regulatory reporting to the relevant agencies
- [✗] Ore/product grade tracking
- [✗] Community programs and commitments
- [✗] Safety drills
- [✗] Contractor management

## C46. 🎰 Gaming & Lottery

- [✗] Player accounts and age verification
- [✗] Responsible gaming controls (limits, self-exclusion)
- [✗] Wallet/ledger that always balances
- [✗] Game results integrity and audit
- [✗] AML monitoring
- [✗] PAGCOR licensing and regulatory requirements
- [✗] Game logs
- [✗] Payout verification
- [✗] Responsible gaming notices

## C47. 💻 Software, SaaS & Digital Platforms

- [✗] Subscription plans and pricing tiers
- [✗] Free trials
- [✗] Recurring billing
- [✗] Usage-based billing, if applicable
- [✗] Feature limits per plan
- [✗] Tenant/workspace management
- [✗] Team members and invitations
- [✗] Onboarding flows
- [✗] In-app notifications and announcements
- [✗] Public API and API keys
- [✗] Webhooks for customers
- [✗] Status page
- [✗] Customer data export
- [✗] Account deletion
- [✗] Prorated plan changes
- [✗] Invoices and receipts
- [✗] Dunning (failed payment retries and reminders)
- [✗] Coupons and discounts
- [✗] Usage metering
- [✗] Audit logs per workspace
- [✗] Customer SSO
- [✗] Roles per workspace

## C48. 💬 Social, Community & Content Platforms

- [✗] User profiles
- [✗] Posts, comments, and reactions
- [✗] Following/friends
- [✗] Messaging
- [✗] Content moderation
- [✗] Reporting of abusive content
- [✗] Blocking and muting users
- [✗] Spam and bot prevention
- [✗] Age restrictions and minor protection
- [✗] Takedown process for illegal or infringing content
- [✗] Community guidelines
- [✗] Feeds and ranking
- [✗] Hashtags and search
- [✗] Media uploads
- [✗] Live streaming
- [✗] Verified accounts
- [✗] Appeals for moderation decisions

## C49. 🧑‍💻 Gig & On-Demand Service Platforms

- [✗] Customer and provider accounts
- [✗] Provider verification
- [✗] Service requests and matching
- [✗] Real-time location tracking
- [✗] Pricing and surge rules
- [✗] In-app payments and provider payouts
- [✗] Ratings for both sides
- [✗] Dispute handling
- [✗] Safety features (emergency button, trip sharing)
- [✗] Cancellation fees
- [✗] Tips
- [✗] Provider incentives
- [✗] Provider document checks and expiry
- [✗] Insurance for providers and customers
- [✗] Demand heat maps

## C50. 🏬 Public Markets & Market Stall Management

- [✗] Stall inventory and stall map
- [✗] Stall holders/lessees records
- [✗] Stall applications and awarding
- [✗] Lease contracts and renewals
- [✗] Daily/monthly stall fees and collection
- [✗] Arrears and penalties
- [✗] Market cleanliness and inspection reports
- [✗] Weights and measures inspection, if applicable
- [✗] Price monitoring of basic commodities
- [✗] Stall transfers and subleasing rules
- [✗] Electricity and water billing per stall

## C51. 🏪 Sari-Sari Stores, Micro-Retail, Wholesale & Distribution

- [✗] Simple POS for small stores
- [✗] Credit/"utang" tracking per customer
- [✗] Small-quantity selling (tingi) and pack conversions
- [✗] Supplier orders and deliveries
- [✗] Load/e-load and bills payment services, if applicable
- [✗] Wholesale price tiers
- [✗] Distributor route sales and van selling
- [✗] Sales agent orders and collections
- [✗] Delivery schedules to retailers

## C52. 🔩 Hardware & Construction Supply Stores

- [✗] Large catalog with sizes and specifications
- [✗] Selling by length, weight, or volume
- [✗] Quotations for contractors
- [✗] Contractor accounts and credit terms
- [✗] Delivery scheduling and delivery fees
- [✗] Bulk orders and partial deliveries
- [✗] Cutting/custom services, if applicable
- [✗] Inventory by brand, size, and grade
- [✗] Returns of excess materials

## C53. 🥖 Bakeries, Food Production & Catering

- [✗] Recipes and ingredient costing
- [✗] Production planning per day
- [✗] Batch production records
- [✗] Ingredient inventory and expiry
- [✗] Wastage and leftovers
- [✗] Branch/outlet deliveries
- [✗] Catering packages and menus
- [✗] Catering event bookings and headcount
- [✗] Equipment and staff assignment per event
- [✗] Food safety and sanitation records

## C54. 💧 Water Refilling Stations & Delivery

- [✗] Customer records and addresses
- [✗] Container (gallon) tracking and deposits
- [✗] Refill and delivery orders
- [✗] Delivery routes and riders
- [✗] Borrowed/returned containers
- [✗] Water quality test records and permits
- [✗] Filter and equipment maintenance logs
- [✗] Subscription/regular delivery schedules
- [✗] Container loss tracking

## C55. 💐 Wedding, Event Planning & Photography Studios

- [✗] Client inquiries and bookings
- [✗] Packages and add-ons
- [✗] Event timelines and checklists
- [✗] Supplier/vendor coordination
- [✗] Payment schedules and deposits
- [✗] Staff/crew scheduling
- [✗] Photo/video delivery to clients (galleries, downloads)
- [✗] Contracts and cancellation policies

## C56. 🧸 Childcare, Daycare & Elderly Care

- [✗] Child/resident records
- [✗] Parent/guardian or family contacts
- [✗] Authorized pick-up persons
- [✗] Daily attendance (check-in/check-out)
- [✗] Health, allergy, and medication notes
- [✗] Daily activity and incident reports
- [✗] Caregiver schedules
- [✗] Billing and fees
- [✗] Emergency procedures
- [✗] Licensing/accreditation requirements (e.g. DSWD, ECCD), if applicable
- [✗] Extra protection for minors' and elderly persons' data
- [✗] Daily meal and nap logs
- [✗] Photo/activity updates to families
- [✗] Visitor logs

## C57. 🧰 Home & Technical Services (Cleaning, Pest Control, Aircon, Plumbing, Electrical)

- [✗] Service requests and bookings
- [✗] Technician scheduling and dispatch
- [✗] Job orders and checklists
- [✗] Before/after photos
- [✗] Parts and materials used
- [✗] Service reports signed by the customer
- [✗] Warranty on service
- [✗] Preventive maintenance contracts and reminders
- [✗] Chemical usage logs (pest control), if applicable

## C58. ☀️ Solar & Renewable Energy Installers

- [✗] Site surveys and load assessments
- [✗] System sizing and quotations
- [✗] Equipment inventory (panels, inverters, batteries)
- [✗] Installation projects and schedules
- [✗] Net metering application tracking, if applicable
- [✗] Energy production monitoring
- [✗] Warranty and maintenance schedules

## C59. 🖥️ IT Services, Computer Shops & Gadget Repair

- [✗] Repair job orders with device details and serial numbers
- [✗] Device condition and accessories received
- [✗] Diagnostics and repair estimates
- [✗] Customer approval before repair
- [✗] Parts used and costs
- [✗] Repair status and pickup notifications
- [✗] Warranty on repairs
- [✗] Computer rental time tracking (internet cafés), if applicable
- [✗] Managed IT service contracts and support tickets
- [✗] Data backup/recovery services
- [✗] Device pickup authorization

## C60. 🏢 Coworking Spaces & Venue Rentals

- [✗] Spaces, rooms, and desks
- [✗] Hourly/daily/monthly bookings
- [✗] Memberships and access passes
- [✗] Access control (QR, RFID)
- [✗] Equipment and add-on rentals
- [✗] Billing and invoices

## C61. 🚙 Vehicle & Equipment Rentals

- [✗] Vehicle/equipment inventory
- [✗] Availability calendar
- [✗] Rental bookings
- [✗] Customer ID and license verification
- [✗] Rental agreements
- [✗] Security deposits
- [✗] Pick-up and return inspection (photos, fuel, mileage)
- [✗] Damage and late-return charges
- [✗] Maintenance schedules

## C62. 🛺 Transport Associations & Cooperatives (TODA, Jeepney/Van Cooperatives)

- [✗] Member/operator and driver records
- [✗] Unit/vehicle records and franchise numbers
- [✗] Membership dues and contributions
- [✗] Dispatching and queueing at terminals
- [✗] Routes and fares
- [✗] Violations and sanctions
- [✗] LGU/LTFRB permit expiry tracking

## C63. ⚓ Shipping, Ports & Maritime

- [✗] Vessel records
- [✗] Voyage schedules
- [✗] Passenger ticketing and manifests
- [✗] Cargo booking and bills of lading
- [✗] Port fees and charges
- [✗] Crew records and certificates
- [✗] Crew deployment and contracts (manning agencies)
- [✗] Certificate expiry alerts
- [✗] Safety and weather cancellations
- [✗] MARINA, PPA, PCG, or DMW requirements, if applicable
- [✗] Weather advisories integration
- [✗] Cargo weight and dangerous goods declarations

**Enterprise depth:**

### C63.1 🛳️ Passenger Shipping & Fast Craft

- [✗] Routes, trips, and vessel assignment
- [✗] Fare classes (economy, tourist, business, cabins)
- [✗] Seat/berth/cabin assignment
- [✗] Online booking and ticketing
- [✗] Discounts (senior citizen, PWD, student, children)
- [✗] Passenger manifest with names, ages, and contact numbers
- [✗] Pre-departure manifest submission to authorities, as required
- [✗] Vessel capacity enforcement (no overloading)
- [✗] Check-in and boarding scanning
- [✗] Weather/trip suspension handling with rebooking and refunds
- [✗] Baggage and excess baggage fees

### C63.2 🚛 Roll-On/Roll-Off (RoRo) & Vehicle Shipping

- [✗] Vehicle bookings by type and size
- [✗] Lane meter/space allocation on the vessel
- [✗] Vehicle details (plate number, weight, driver)
- [✗] Cargo declared per vehicle
- [✗] Loading plan and loading sequence
- [✗] Rolling cargo rates

### C63.3 📦 Cargo & Container Shipping

- [✗] Cargo bookings
- [✗] Bills of lading
- [✗] Cargo manifests
- [✗] Container inventory and tracking (container numbers, status, location)
- [✗] Empty container management
- [✗] Demurrage and detention charges
- [✗] Dangerous goods declarations and handling rules
- [✗] Reefer (refrigerated) container monitoring
- [✗] Cargo tracking for customers
- [✗] Freight rates and tariffs
- [✗] Cargo claims (damage, loss, shortage)

### C63.4 🏗️ Port & Terminal Operations

- [✗] Vessel arrival and departure schedules
- [✗] Berth allocation and planning
- [✗] Pilotage and tugboat services
- [✗] Terminal yard management (container positions)
- [✗] Gate in/gate out transactions
- [✗] Equipment (cranes, forklifts) assignment
- [✗] Port fees, wharfage, and other charges
- [✗] Port security and access passes
- [✗] Integration with customs systems, as required

### C63.5 🧭 Vessel Operations & Fleet Management

- [✗] Voyage planning
- [✗] Vessel positions and tracking (AIS integration)
- [✗] Daily vessel reports (noon reports, fuel, speed, weather)
- [✗] Fuel consumption and bunkering records
- [✗] Vessel certificates and expiry tracking
- [✗] Classification society surveys
- [✗] Planned maintenance system for engines and equipment
- [✗] Spare parts inventory on board
- [✗] Dry-docking schedules
- [✗] Port state control inspections and findings

### C63.6 👨‍✈️ Crew Management

- [✗] Seafarer records
- [✗] Certificates of competency and training (STCW) with expiry alerts
- [✗] Medical certificates
- [✗] Crew contracts and deployment
- [✗] Crew rotation and relief planning
- [✗] Rest hours/work hours monitoring
- [✗] Crew payroll and allotments to families
- [✗] Manning agency coordination

### C63.7 🦺 Safety, Security & Environmental Compliance

- [✗] Safety management system (ISM Code) records
- [✗] Ship security plan (ISPS Code) records
- [✗] Drills and emergency exercises
- [✗] Incident and near-miss reports
- [✗] Pollution prevention records (MARPOL)
- [✗] Garbage and oil record books
- [✗] Life-saving and firefighting equipment inspections

### C63.8 💼 Commercial & Back Office

- [✗] Chartering contracts
- [✗] Agency and port agent management
- [✗] Customer accounts and credit terms
- [✗] Freight invoicing and collections
- [✗] Revenue per voyage and route
- [✗] Voyage costing (fuel, port fees, crew)
- [✗] Insurance (hull, cargo, protection & indemnity)

## C64. ♻️ Environmental & Waste Management

- [✗] Waste collection schedules and routes
- [✗] Collection points and households served
- [✗] Waste volume/weight records by type
- [✗] Segregation compliance monitoring
- [✗] Recycling and materials recovery facility (MRF) records
- [✗] Junk shop / recyclable buying and selling
- [✗] Hazardous waste tracking, if applicable
- [✗] Environmental permits and compliance reports
- [✗] Air/water quality monitoring, if applicable
- [✗] Complaints and violation reports

## C65. 🚨 Disaster Risk Reduction & Emergency Response

- [✗] Hazard maps
- [✗] Early warning alerts (SMS, push, sirens)
- [✗] Evacuation centers and capacity
- [✗] Evacuee registration and tracking
- [✗] Relief goods inventory and distribution
- [✗] Duplicate relief distribution prevention
- [✗] Damage and needs assessment
- [✗] Missing persons and family reunification
- [✗] Emergency incident reporting with location
- [✗] Responder and vehicle dispatch
- [✗] Offline operation when networks are down
- [✗] Reports to MDRRMO/PDRRMO/NDRRMC, if applicable
- [✗] Volunteer management
- [✗] Donation intake for disasters

## C66. 🔬 Research Institutions, Thesis Repositories & Academic Publishing

- [✗] Research projects and researchers
- [✗] Proposals and approval workflow
- [✗] Ethics review, if applicable
- [✗] Research funding and grants
- [✗] Thesis/capstone submissions
- [✗] Adviser and panel assignments
- [✗] Defense schedules and evaluation
- [✗] Revisions and approval tracking
- [✗] Repository with search by title, author, year, keyword, and program
- [✗] Access levels (public abstract, restricted full text)
- [✗] Plagiarism/similarity check records
- [✗] Citations and metadata
- [✗] Copyright and author permission records
- [✗] Journal submissions and peer review, if applicable
- [✗] Research outputs (publications, patents, extensions)
- [✗] Research agenda alignment

## C67. 🎖️ Uniformed & Civic Training Units (ROTC, NSTP, CWTS, Security Training)

- [✗] Cadet/trainee records
- [✗] Units, companies, and platoons
- [✗] Training schedules
- [✗] Attendance
- [✗] Grades and evaluations
- [✗] Merits and demerits
- [✗] Ranks and promotions
- [✗] Equipment and uniform issuance
- [✗] Reviewers, quizzes, and exams
- [✗] Certificates of completion
- [✗] Reports to the school and supervising agency

## C68. 🖼️ Museums, Arts & Cultural Organizations

- [✗] Collection catalog (items, artists, provenance)
- [✗] Item photos and condition reports
- [✗] Exhibitions
- [✗] Loans of items to/from other institutions
- [✗] Visitor tickets and visitor counts
- [✗] Guided tour bookings
- [✗] Artist/member records
- [✗] Grants and sponsorships

## C69. 🏆 Sports Leagues, Tournaments & Esports

- [✗] Teams and players
- [✗] Player eligibility and registration
- [✗] Tournament brackets and schedules
- [✗] Match results and standings
- [✗] Player statistics
- [✗] Referees/officials assignment
- [✗] Registration fees
- [✗] Live score updates, if needed
- [✗] Anti-cheating measures (esports)
- [✗] Venue/court scheduling
- [✗] Medical clearances for players

## C70. 🏡 Homeowners Associations, Condominiums & Clubs

- [✗] Units/lots and owners/tenants
- [✗] Association dues and billing
- [✗] Arrears and penalties
- [✗] Gate passes and visitor logs
- [✗] Vehicle stickers
- [✗] Amenity reservations (clubhouse, court, pool)
- [✗] Maintenance requests
- [✗] Announcements
- [✗] Elections and voting
- [✗] Board meeting minutes and resolutions
- [✗] Financial transparency reports to members
- [✗] Rules and violations
- [✗] Contractor/worker gate passes

## C71. 📰 Media, News & Publishing

- [✗] Articles and content management
- [✗] Editorial workflow (draft, review, publish)
- [✗] Authors and contributors
- [✗] Categories and tags
- [✗] Scheduled publishing
- [✗] Media library (images, videos)
- [✗] Subscriptions/paywalls, if applicable
- [✗] Advertisements and sponsors
- [✗] Corrections and update history
- [✗] Copyright and image licensing records
- [✗] Newsletters
- [✗] Analytics per article

## C72. 🎨 Creative Agencies, Software Studios & Freelancers

- [✗] Client records
- [✗] Inquiries and proposals
- [✗] Contracts and scope of work
- [✗] Projects and milestones
- [✗] Change requests
- [✗] Time tracking
- [✗] Invoices and payments per milestone
- [✗] Client approvals and sign-offs
- [✗] File/deliverable handover
- [✗] Portfolio/showcase of completed projects
- [✗] Maintenance and support agreements
- [✗] Credentials handed over securely
- [✗] Quotations/estimates
- [✗] Revision limits per contract

## C73. ⛽ Gas Stations & Fuel/LPG Dealers

- [✗] Fuel products and pump/nozzle records
- [✗] Pump meter readings per shift
- [✗] Tank inventory and dipstick readings
- [✗] Fuel deliveries
- [✗] Price changes with effective date and time
- [✗] Fleet/corporate fuel accounts
- [✗] Fleet cards and fuel vouchers
- [✗] LPG cylinder inventory and exchange
- [✗] LPG delivery orders
- [✗] Safety inspections and permits
- [✗] Convenience store POS integration

## C74. 🚿 Car Wash & Auto Detailing

- [✗] Service packages by vehicle size
- [✗] Queue/bay assignment
- [✗] Washer/detailer commissions
- [✗] Membership/unlimited wash plans
- [✗] Before-and-after photos
- [✗] Supplies inventory

## C75. 🍔 Franchise Management

- [✗] Franchisee records
- [✗] Franchise agreements and expiry
- [✗] Franchise fees and royalties
- [✗] Sales reporting from each outlet
- [✗] Supply ordering from the franchisor (commissary)
- [✗] Standard operating procedures and manuals
- [✗] Outlet audits and compliance scores
- [✗] Marketing fund contributions
- [✗] Consolidated reports across outlets

## C76. 🤝 Direct Selling & Distributor Networks

- [✗] Distributor/reseller accounts
- [✗] Reseller tiers and pricing
- [✗] Orders from resellers
- [✗] Commission and rebate computation
- [✗] Sales targets and incentives
- [✗] Product stock per reseller
- [✗] Payout requests
- [✗] Rules preventing illegal pyramid structures (income from product sales, not recruitment)

## C77. 👗 Fashion, Garments & Tailoring

- [✗] Product styles, sizes, and colors
- [✗] Customer body measurements
- [✗] Custom orders and fittings
- [✗] Fabric and materials inventory
- [✗] Production/job orders per design
- [✗] Sewer/tailor piece-rate tracking
- [✗] Alterations
- [✗] Collections and seasonal catalogs

## C78. 🪑 Furniture, Woodworking & Custom Fabrication

- [✗] Custom design requests and drawings
- [✗] Quotations with materials and labor
- [✗] Down payments
- [✗] Production stages tracking
- [✗] Materials inventory
- [✗] Delivery and installation scheduling
- [✗] Warranty and repairs

## C79. 💎 Jewelry Stores

- [✗] Item details (metal, karat, weight, stones)
- [✗] Item photos and certificates
- [✗] Gold price updates
- [✗] Custom orders and repairs
- [✗] Buy-back and trade-in
- [✗] Installment/layaway
- [✗] High-value item audit and security

## C80. 👓 Optical Shops

- [✗] Patient records
- [✗] Eye examination results and prescriptions
- [✗] Frames and lens inventory
- [✗] Lens orders to laboratories
- [✗] Order status and pickup notifications
- [✗] HMO/insurance billing
- [✗] Follow-up reminders

## C81. 🐶 Pet Shops, Grooming & Animal Shelters

- [✗] Pet products inventory
- [✗] Grooming appointments
- [✗] Pet profiles and grooming notes
- [✗] Shelter animal intake records
- [✗] Medical/vaccination records of shelter animals
- [✗] Adoption applications and screening
- [✗] Adoption follow-ups
- [✗] Donations and volunteers

## C82. 🦽 Physical Therapy & Rehabilitation Centers

- [✗] Patient records and referrals
- [✗] Assessments and treatment plans
- [✗] Session scheduling
- [✗] Session notes and progress tracking
- [✗] Home exercise programs
- [✗] Packages and billing
- [✗] HMO billing

## C83. 💻 Telemedicine

- [✗] Doctor profiles and schedules
- [✗] Online appointment booking
- [✗] Secure video consultations
- [✗] Patient intake forms
- [✗] E-prescriptions
- [✗] Laboratory requests
- [✗] Medical certificates
- [✗] Online payments
- [✗] Consent for teleconsultation
- [✗] Doctor license verification
- [✗] Encryption and strict access to consultation records

## C84. 🩸 Blood Banks & Donor Programs

- [✗] Donor registration
- [✗] Donor eligibility screening
- [✗] Donation records
- [✗] Blood unit inventory by type and component
- [✗] Expiry tracking
- [✗] Blood requests from hospitals
- [✗] Cross-matching records
- [✗] Donor reminders for next eligible donation
- [✗] Blood drive events

## C85. 🩺 HMOs & Health Plans

- [✗] Member enrollment
- [✗] Plans and benefit limits
- [✗] Accredited hospitals, clinics, and doctors
- [✗] Letters of authorization (LOA)
- [✗] Claims from providers
- [✗] Utilization per member
- [✗] Corporate accounts
- [✗] Member cards/digital IDs
- [✗] Insurance Commission requirements

## C86. 📝 Review Centers, Tutorial Centers & Language Schools

- [✗] Programs and review schedules
- [✗] Enrollment and payments
- [✗] Learning materials
- [✗] Question banks by subject and topic
- [✗] Mock exams with timers and scoring
- [✗] Performance analytics per topic
- [✗] Ranking among reviewees
- [✗] Tutor scheduling and session logs
- [✗] Language proficiency levels and assessments
- [✗] Certificates

## C87. 🚘 Driving Schools

- [✗] Student records
- [✗] Theoretical and practical course packages
- [✗] Lesson scheduling
- [✗] Instructor and vehicle assignment
- [✗] Lesson hours tracking
- [✗] Evaluations
- [✗] Certificates of completion
- [✗] LTO accreditation requirements

## C88. 🎵 Music, Dance & Art Schools

- [✗] Classes and levels
- [✗] Student enrollment
- [✗] Teacher schedules
- [✗] Studio/room booking
- [✗] Instrument rentals
- [✗] Recitals and performances
- [✗] Progress reports

## C89. 🎓 Scholarship Foundations & Grant Programs

- [✗] Scholarship programs and criteria
- [✗] Online applications
- [✗] Document requirements
- [✗] Screening, interviews, and ranking
- [✗] Awarding
- [✗] Allowance disbursement
- [✗] Grade/academic monitoring of scholars
- [✗] Renewal or termination of scholarships
- [✗] Donor/sponsor reports

## C90. 🧑‍🎓 Student Organizations & Student Councils

- [✗] Members and officers
- [✗] Membership fees
- [✗] Events and activities
- [✗] Attendance at events (QR)
- [✗] Event proposals and approvals
- [✗] Organization funds, expenses, and liquidation
- [✗] Announcements
- [✗] Officer elections
- [✗] Accomplishment reports

## C91. 📐 Architecture, Engineering & Surveying Firms

- [✗] Client and project records
- [✗] Proposals and fees
- [✗] Design phases and deliverables
- [✗] Drawing and document versioning
- [✗] Permit applications tracking
- [✗] Site visits and reports
- [✗] Survey data and plans
- [✗] Professional time tracking
- [✗] Billing per milestone

## C92. 🛋️ Interior Design

- [✗] Client briefs
- [✗] Mood boards and design proposals
- [✗] Materials and furniture selections
- [✗] Supplier quotations
- [✗] Project budget tracking
- [✗] Installation schedules
- [✗] Client approvals

## C93. ✈️ Airlines, Airports & Aviation Services

- [✗] Flight schedules
- [✗] Reservations and ticketing
- [✗] Seat assignment
- [✗] Check-in and boarding passes
- [✗] Baggage tracking
- [✗] Passenger manifests
- [✗] Crew scheduling and certifications
- [✗] Aircraft maintenance records
- [✗] Flight delays and cancellations notifications
- [✗] CAAP and other aviation regulatory requirements

**Enterprise depth:**

### C93.1 🎫 Reservations, Inventory & Ticketing

- [✗] Flight inventory per cabin and fare class
- [✗] Passenger name records (PNR)
- [✗] Fare rules (refundability, rebooking fees, baggage allowance)
- [✗] Ancillary services (seats, baggage, meals, insurance)
- [✗] E-tickets and electronic miscellaneous documents (EMD)
- [✗] Group bookings
- [✗] Waitlists
- [✗] Payment and fraud checks
- [✗] Distribution through travel agents, GDS, and NDC channels
- [✗] Promo fares and seat sales

### C93.2 📈 Revenue Management & Pricing

- [✗] Demand forecasting
- [✗] Dynamic pricing
- [✗] Overbooking controls
- [✗] Route profitability reports
- [✗] Load factor reports
- [✗] Revenue accounting (ticket sales vs. flown revenue)
- [✗] Interline and codeshare settlements, if applicable

### C93.3 🛂 Airport Departure Control

- [✗] Online and kiosk check-in
- [✗] Counter check-in
- [✗] Travel document checks
- [✗] Advance passenger information to authorities, as required
- [✗] Boarding passes (barcode standard)
- [✗] Seat changes at check-in
- [✗] Boarding gate scanning
- [✗] Standby and upgrade handling
- [✗] Load and balance (weight and balance) calculations
- [✗] Final passenger and cargo manifests

### C93.4 🧳 Baggage

- [✗] Bag tags with barcode/RFID
- [✗] Baggage tracking at key points (check-in, loading, arrival)
- [✗] Baggage reconciliation with boarded passengers
- [✗] Excess baggage charges
- [✗] Mishandled baggage reports (lost, delayed, damaged)
- [✗] Baggage delivery and compensation

### C93.5 🗓️ Flight Operations & Dispatch

- [✗] Flight schedules and seasonal planning
- [✗] Aircraft assignment (tail assignment)
- [✗] Flight planning (routes, fuel, weather)
- [✗] Dispatch release
- [✗] Flight tracking
- [✗] Delay codes and on-time performance reports
- [✗] Irregular operations (delays, cancellations, diversions) recovery
- [✗] Passenger rebooking and notifications during disruptions

### C93.6 👩‍✈️ Crew Management

- [✗] Crew rostering
- [✗] Flight duty time and rest limit checks
- [✗] Crew qualifications, licenses, and medical expiry
- [✗] Recurrent training records
- [✗] Crew pairing and reserve/standby crew
- [✗] Crew hotel and transport

### C93.7 🔧 Aircraft Maintenance (MRO)

- [✗] Aircraft and component records
- [✗] Maintenance programs and schedules (hours, cycles, calendar)
- [✗] Work orders and task cards
- [✗] Airworthiness directives and service bulletin tracking
- [✗] Defect reports and deferred items
- [✗] Parts inventory with traceability and certificates
- [✗] Tool calibration records
- [✗] Technician licenses and authorizations
- [✗] Aircraft technical logbook

### C93.8 🏢 Airport Operations

- [✗] Airport operational database (flights, gates, stands)
- [✗] Gate and stand allocation
- [✗] Flight information display systems (FIDS)
- [✗] Ground handling services and billing
- [✗] Airport fees and charges
- [✗] Slot coordination, if applicable
- [✗] Airport security access passes

### C93.9 📦 Air Cargo

- [✗] Cargo bookings and air waybills
- [✗] Cargo acceptance and screening
- [✗] Dangerous goods checks
- [✗] Cargo capacity per flight
- [✗] Cargo tracking

### C93.10 🤝 Customer Service, Loyalty & Compliance

- [✗] Refunds and travel credits
- [✗] Passenger rights compensation (delays, cancellations, overbooking) per regulations
- [✗] Special assistance (wheelchair, unaccompanied minors, medical cases)
- [✗] Frequent flyer program (points, tiers, redemption)
- [✗] Customer feedback and complaints
- [✗] Safety management system records
- [✗] Aviation security records
- [✗] CAAP and Civil Aeronautics Board regulatory reports, if applicable

## C94. 🤿 Tour Activity Operators (Diving, Island Hopping, Adventure)

- [✗] Activity schedules and capacity
- [✗] Bookings and waivers
- [✗] Guide/instructor assignment
- [✗] Boat and equipment assignment
- [✗] Weather cancellations
- [✗] Certification records (e.g. diving)
- [✗] Environmental fees collection
- [✗] Safety briefings and incident reports

## C95. 🎢 Theme Parks, Cinemas & Recreation Centers

- [✗] Ticketing and passes
- [✗] Showtimes and seat selection (cinemas)
- [✗] Ride/attraction capacity
- [✗] Concessions POS
- [✗] Gate entry scanning
- [✗] Ride maintenance and safety inspections
- [✗] Visitor counts

## C96. 🎤 Bars, KTV & Nightlife

- [✗] Room/table reservations
- [✗] Room time tracking and charges
- [✗] Orders per room/table
- [✗] Minimum consumable rules
- [✗] Bottle service/keep tracking
- [✗] Age verification
- [✗] Shift sales reports

## C97. 👕 Thrift, Consignment & Second-Hand Stores

- [✗] Item intake and condition grading
- [✗] Consignor records
- [✗] Consignment terms and commission split
- [✗] Consignor payouts
- [✗] Unique item tracking (one-of-a-kind stock)
- [✗] Markdown schedules for aging items
- [✗] Unsold item returns

## C98. ⚖️ Courts, Prosecution & Legal Aid Offices

- [✗] Case filing and docketing
- [✗] Case numbers and case status
- [✗] Hearing schedules and calendars
- [✗] Parties, counsel, and witnesses
- [✗] Orders and decisions
- [✗] Subpoenas and notices
- [✗] Legal aid applications and eligibility
- [✗] Case assignment to lawyers
- [✗] Strict confidentiality and access control
- [✗] Records retention

---

# Part D — Laws & Regulations (Philippines)

Check which ones apply to the client's business. Always verify the current version.

## D1. 🔒 Data, Privacy & Cyber Laws

- [✓] Data Privacy Act of 2012 (RA 10173) and NPC rules
- [✗] Cybercrime Prevention Act of 2012 (RA 10175)
- [✗] Electronic Commerce Act of 2000 (RA 8792) — validity of electronic documents and signatures
- [✗] Internet Transactions Act of 2023 (RA 11967) — online sellers and platforms
- [✗] SIM Registration Act (RA 11934), if handling SIM/telecom data
- [✗] Philippine Identification System Act (RA 11055), if using PhilSys/National ID verification

## D2. 💰 Tax & Business Registration

- [✗] National Internal Revenue Code and BIR regulations
- [✗] Ease of Paying Taxes Act (RA 11976) — invoicing rules
- [✗] BIR registration/accreditation of computerized accounting systems, POS, and invoicing systems
- [✗] BIR electronic invoicing / sales reporting, if required for the taxpayer
- [✗] Books of accounts requirements (manual, loose-leaf, or computerized)
- [✗] Business registration (DTI, SEC, or CDA) and local permits

## D3. 👷 Labor & Employment

- [✗] Labor Code of the Philippines and DOLE rules
- [✗] Regional minimum wage orders
- [✗] 13th Month Pay Law (PD 851)
- [✗] Social Security Act (RA 11199)
- [✗] Universal Health Care Act (RA 11223) / PhilHealth rules
- [✗] Pag-IBIG Fund Law (RA 9679)
- [✗] Expanded Maternity Leave Law (RA 11210)
- [✗] Paternity leave, solo parent leave, and other statutory leaves
- [✗] Telecommuting Act (RA 11165), if work-from-home
- [✗] Occupational Safety and Health Standards (RA 11058)

## D4. 🛒 Consumer Protection & Discounts

- [✗] Consumer Act of the Philippines (RA 7394)
- [✗] Expanded Senior Citizens Act (RA 9994) — 20% discount and VAT exemption
- [✗] Magna Carta for Persons with Disability (RA 7277, as amended) — 20% discount and VAT exemption
- [✗] Expanded Solo Parents Welfare Act (RA 11861) — benefits and discounts, where applicable
- [✗] Price Act (RA 7581), for basic necessities
- [✗] Financial Products and Services Consumer Protection Act (RA 11765), for financial services

## D5. 🏦 Finance & Banking

- [✗] Anti-Money Laundering Act (RA 9160, as amended) and AMLC rules
- [✗] BSP regulations (banks, e-money issuers, remittance, pawnshops, payment systems)
- [✗] BSP IT risk management and cybersecurity circulars
- [✗] National Payment Systems Act (RA 11127)
- [✗] Lending Company Regulation Act (RA 9474) and Financing Company Act (RA 8556), with SEC rules
- [✗] Truth in Lending Act (RA 3765)
- [✗] Philippine Cooperative Code (RA 9520) and CDA rules
- [✗] Insurance Code and Insurance Commission rules

## D6. 🏛️ Government

- [✗] New Government Procurement Act (RA 12009) and its implementing rules
- [✗] PhilGEPS requirements
- [✗] COA rules and audit requirements
- [✗] Ease of Doing Business and Efficient Government Service Delivery Act (RA 11032)
- [✗] National Archives of the Philippines Act (RA 9470) — records management
- [✗] DICT standards and guidelines for government ICT
- [✗] Freedom of Information rules, where applicable

## D7. 🏥 Health, Education & Other Sectors

- [✗] DOH licensing and health information standards
- [✗] PhilHealth eClaims and reporting
- [✗] FDA rules (pharmacies, food, drugs, cosmetics)
- [✗] Comprehensive Dangerous Drugs Act (RA 9165) recordkeeping, if handling dangerous drugs
- [✗] DepEd, CHED, and TESDA rules and reporting formats
- [✗] Child protection rules for systems used by minors
- [✗] Real Estate Service Act (RA 9646) and Maceda Law (RA 6552)
- [✗] Rent control rules, where applicable
- [✗] Tourism Act (RA 9593) and DOT accreditation
- [✗] Philippine Fisheries Code (RA 8550, as amended by RA 10654) and DA/BFAR rules
- [✗] LTFRB/LTO rules for transport
- [✗] ERC, NTC, LWUA, and other sector regulators
- [✗] PAGCOR rules for gaming
- [✗] Intellectual Property Code (RA 8293) — software licensing, content, trademarks

## D8. 🌾 Agriculture, Fisheries, Environment & Other Sectors

- [✗] Agriculture and Fisheries Modernization Act (RA 8435)
- [✗] Philippine Fisheries Code (RA 8550, as amended by RA 10654) and BFAR rules
- [✗] Organic Agriculture Act (RA 10068, as amended by RA 11511)
- [✗] Food Safety Act (RA 10611)
- [✗] Rice Tariffication Law (RA 11203), for rice-related programs
- [✗] Animal Welfare Act (RA 8485, as amended by RA 10631)
- [✗] National Meat Inspection Service (NMIS) rules, for slaughterhouses and meat processing
- [✗] DA registry systems (e.g. RSBSA) and BFAR registries (e.g. FishR, BoatR)
- [✗] Philippine Crop Insurance Corporation (PCIC) requirements, if handling insurance
- [✗] Local Government Code (RA 7160) and municipal ordinances (markets, fisheries, permits)
- [✗] Ecological Solid Waste Management Act (RA 9003)
- [✗] Clean Water Act (RA 9275) and Clean Air Act (RA 8749)
- [✗] Environmental Impact Statement System (PD 1586) and DENR rules
- [✗] Philippine Disaster Risk Reduction and Management Act (RA 10121)
- [✗] Renewable Energy Act (RA 9513) and ERC net-metering rules
- [✗] Early Years Act (RA 10410) and DSWD licensing, for childcare and care facilities
- [✗] Magna Carta for Homeowners and Homeowners' Associations (RA 9904) and DHSUD rules
- [✗] Condominium Act (RA 4726)
- [✗] MARINA, PPA, and Philippine Coast Guard rules, for shipping and maritime
- [✗] Civil Aviation Authority of the Philippines (CAAP) rules, for aviation
- [✗] Land Transportation Office (LTO) rules, for driving schools and vehicles
- [✗] Anti-pyramiding rules (SEC), for direct selling and reseller networks
- [✗] Professional Regulation Commission (PRC) licensing rules, for licensed professionals
- [✗] National Voluntary Blood Services Act (RA 7719), for blood banks
- [✗] Air Passenger Bill of Rights (DOTC-DTI Joint Administrative Order No. 1, s. 2012), for airlines
- [✗] Philippine Merchant Marine / MARINA circulars for domestic shipping
- [✗] Filipino Sign Language Act (RA 11106), for accessibility of public services and media

---

# Part E — International Standards & Laws (If the Client Requires Them)

## E1. 🌍 Privacy Laws in Other Countries

- [✗] GDPR (European Union users)
- [✗] UK GDPR (United Kingdom users)
- [✗] CCPA/CPRA (California users)
- [✗] Other countries' privacy laws where users are located
- [✗] Cross-border data transfer requirements

## E2. 🛡️ Security & Management Standards

- [✗] ISO/IEC 27001 — information security management
- [✗] SOC 2 — security, availability, confidentiality controls (common for SaaS/BPO clients)
- [✗] PCI DSS — card payment data
- [✗] NIST Cybersecurity Framework
- [✗] CIS Controls / CIS Benchmarks for servers
- [✗] OWASP ASVS — application security verification
- [✗] ISO 22301 — business continuity
- [✗] ISO 9001 — quality management
- [✗] ITIL — IT service management

## E3. 🏥 Industry Standards

- [✗] HIPAA (US health data)
- [✗] HL7 / FHIR (health data exchange)
- [✗] ICD-10 / ICD-11 (diagnosis codes)
- [✗] ISO 20022 (financial messaging)
- [✗] SOX (US public company financial controls)
- [✗] IFRS / PFRS (accounting standards)
- [✗] EDI standards (supply chain document exchange)
- [✗] GS1 barcodes (retail products)
- [✗] DICOM (medical imaging)
- [✗] IATA standards (e.g. NDC, bar-coded boarding passes, baggage tracking)
- [✗] ICAO standards (aviation)
- [✗] IMO conventions: SOLAS (safety), MARPOL (pollution), STCW (seafarer training)
- [✗] ISM Code (ship safety management) and ISPS Code (ship and port security)
- [✗] Maritime Labour Convention, 2006 (seafarers' working conditions)

## E4. ♿ Accessibility Standards

- [✓] WCAG 2.2 (web accessibility guidelines)
- [✗] Accessibility requirements for government or public-facing systems
- [✓] WAI-ARIA authoring practices for custom components
- [✗] Mobile platform accessibility guidelines (Android and iOS)
- [✗] PDF/UA for accessible PDF documents, if required

---

# Part F — Enterprise & Regulated-Industry Security Additions

Extra security measures on top of `security-audit-checklist.md` for enterprises, banks, hospitals, government, and other high-risk systems.

## F1. 🧭 Security Governance

- [✗] Information security policy
- [✗] Acceptable use policy
- [✗] Data classification policy
- [✗] Access control policy
- [✓] Incident response policy
- [✗] Assigned security officer / CISO
- [✓] Data Protection Officer (DPO)
- [✗] Risk assessments
- [✗] Privacy Impact Assessments
- [✗] Threat modeling for major features
- [✗] Security requirements in every project

## F2. 🛰️ Security Operations

- [✗] Security Operations Center (SOC) or managed security provider
- [✗] Centralized log collection (SIEM)
- [✗] 24/7 alerting and on-call security response
- [✗] Intrusion detection/prevention (IDS/IPS)
- [✗] Web Application Firewall (WAF)
- [✗] Endpoint detection and response (EDR) on servers and company devices
- [✗] Vulnerability scanning on a schedule
- [✓] Patch management process with deadlines
- [✗] Regular professional penetration tests
- [✗] Bug bounty or vulnerability disclosure program

## F3. 🌐 Network & Infrastructure Security

- [✗] Network segmentation (separating public, app, and database layers)
- [✗] Private networks for databases and internal services
- [✗] VPN or zero-trust access for internal systems
- [✗] Bastion/jump host for server access
- [✗] DDoS protection
- [✗] Hardened server configurations (CIS Benchmarks)
- [✗] Infrastructure as code with security review

## F4. 🔐 Data Protection

- [✓] Encryption at rest for databases, files, and backups
- [✓] Encryption in transit everywhere, including internal traffic
- [✗] Key management service or hardware security module (HSM)
- [✗] Encryption key rotation
- [✗] Data masking in non-production environments
- [✗] Data Loss Prevention (DLP) controls
- [✗] Tokenization of highly sensitive data (e.g. card numbers)
- ~~[✓] Secure data disposal~~

## F5. 🧍 Insider Threat & People Security

- [✗] Background checks for staff with sensitive access
- ~~[✓] Least-privilege access for employees~~
- ~~[✓] Immediate access removal when staff leave~~
- ~~[✓] Monitoring of privileged user activity~~
- [✗] Security awareness training for all staff
- [✗] Phishing simulation exercises
- [✗] Clean desk and screen lock policies
- [✓] Confidentiality agreements

## F6. 🤝 Third-Party & Vendor Risk

- [✗] Vendor security assessments
- [✗] Data processing agreements with vendors
- ~~[✓] List of all third-party services that receive data~~
- [✗] Vendor access reviewed and removed when no longer needed
- [✗] Software bill of materials (SBOM), if required

## F7. 🏢 Physical & Device Security

- [✓] Secure data center or reputable cloud provider
- [✗] Physical access control for server rooms
- [✗] Company device management (MDM)
- [✗] Full-disk encryption on laptops
- [✗] Remote wipe for lost devices
- [✗] Rules for personal devices (BYOD)

## F8. 📋 Compliance Evidence & Audits

- [✗] Evidence collected for each control (logs, screenshots, policies)
- [✗] Internal security audits
- [✗] External audits/certifications, if required
- [✗] Regulatory reporting of security incidents within required deadlines
- [✗] Records of security training, access reviews, and tests

---

# Quick Rule

**Do not automatically implement everything on this list.**

For every item, ask:

> **"Does this client's business, size, and industry need this?"**

If **yes** → include it in the requirements/design and implement it at the appropriate stage.

If **no** → don't add unnecessary complexity.

Industry requirements come mainly from talking to the client and checking that industry's current laws. This checklist helps make sure nothing important is forgotten.
