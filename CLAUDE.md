   # BMPC PROJECT MANAGEMENT SYSTEM

## Complete System Development Specification

Build a complete, production-ready **Project Management System for Barbaza Multi-Purpose Cooperative (BMPC)**.

This is an internal web-based system for registering, managing, monitoring, and documenting BMPC infrastructure and other approved projects.

Do not create a simple prototype or static UI. Build a fully functional application with database persistence, authentication, role-based access control, project management, project assignment, budget monitoring, timeline monitoring, materials tracking, contractor/provider management, document management, reports, notifications, and audit logs.

---

The system's main process is:

**Approved Project Outside System**
↓
**Admin/CEO registers project**
↓
**Admin/CEO enters project information**
↓
**Admin/CEO assigns Project Personnel**
↓
**Project Personnel manages/updates assigned project**
↓
**Finance & Accounting monitors project budget and financial utilization**
↓
**Admin/CEO monitors overall project progress**
↓
**Project completed**

---

# 2. SYSTEM USERS

There are exactly **3 system user roles**.

## USER 1 — ADMIN / CEO

Admin and CEO are treated as ONE role.

Role:

`admin`

Responsibilities:

* Register approved projects
* Manage all projects
* Edit project information
* Assign project personnel
* Manage project documents
* Monitor project progress
* Monitor project timeline
* View budget information
* Manage contractors/providers
* View project reports
* Manage users
* View audit logs
* Manage system settings

The Admin/CEO has full access to project management functions.

---

# USER 2 — PROJECT PERSONNEL

Role:

`project_personnel`

This is a single system role.

Do NOT create separate roles for:

* OIC
* Staff
* Branch Manager
* Foreman
* Contractor

Instead, Project Personnel has a separate `position_type`.

Allowed position types:

* OIC
* Staff
* Branch Manager
* Foreman
* Contractor

A Project Personnel user can be assigned to one or more projects.

They can only access projects assigned to them.

They can:

* View assigned project
* Update project progress
* Update completion percentage
* Update timeline/progress information
* Record accomplishments
* Record activities
* Record materials used
* Upload progress reports
* Upload accomplishment reports
* Upload progress photos
* Report project issues
* Report delays
* Record actual dates
* View project budget
* View project documents
* View project timeline

They MUST NOT be able to:

* Change approved budget
* Approve projects
* Delete projects
* Modify the official approved design
* Modify financial transactions
* Change project ownership
* Change system settings

---

# USER 3 — FINANCE & ACCOUNTING

Role:

`finance_accounting`

Finance and Accounting are one system role.

Responsibilities:

* View project budgets
* Record project expenses
* Monitor project financial utilization
* Track remaining budget
* Monitor budget overruns
* Track supplemental budgets
* View project financial history
* Generate financial reports

Finance & Accounting MUST NOT be able to:

* Approve projects
* Change the original approved budget
* Modify project designs
* Assign project personnel
* Delete projects
* Change project ownership

---

# 3. TECHNOLOGY STACK

Use the following technology stack.

## Backend

* PHP 8.2+
* Laravel 12+
* Laravel Eloquent ORM
* Laravel Authentication
* Laravel Policies/Gates
* Laravel Notifications
* Laravel Storage

## Frontend

* Blade
* Tailwind CSS
* JavaScript
* Alpine.js
* Lucide Icons

Do not use React or Vue unless there is a strong technical reason.

## Database

* MySQL 8+

## Reports

* DomPDF or a Laravel-compatible PDF library
* Laravel Excel / PhpSpreadsheet for Excel exports

## Development Tools

* VS Code
* Composer
* NPM
* Git
* GitHub

---

# 4. SYSTEM ARCHITECTURE

Use a clean Laravel architecture.

Use:

* Models
* Controllers
* Form Requests
* Policies
* Services for complex business logic
* Blade components
* Database migrations
* Seeders
* Factories
* Notifications
* Events/listeners where useful

Avoid:

* Massive controllers
* Business logic inside Blade
* Hard-coded business rules
* Duplicate code
* Unnecessary dependencies
* Fake functionality

---

# 5. PROJECT LIFECYCLE

The system starts at project registration.

Project statuses:

### Registered

Project has been entered into the system after already being approved outside the system.

### Ongoing

Project implementation has started.

### On Hold

Project temporarily stopped.

### Completed

Project has been completed.

### Cancelled

Project has been cancelled after registration.

Do NOT create Board-related statuses.

Do NOT create:

* Proposed
* Pending Board Approval
* Board Approved
* Board Rejected

The Board is outside the system.

---

# 6. STAGE 3 — PROJECT REGISTRATION

This is the beginning of the system.

Admin registers an already-approved project.

Registration form sections:

## Project Information

Fields:

* Project Code
* Project Title
* Project Description
* Project Category
* Project Type
* Project Location
* Project Objective
* Remarks

Automatically generate project code:

`BMPC-PRJ-2026-0001`

Ensure uniqueness.

---

# 7. APPROVED PROJECT INFORMATION

Because approval has already happened outside the system, the Admin records the approved project information.

Required:

* Approved Budget
* Approved project plan
* Approved project design
* Supporting project documents

Do not create an approval workflow.

---

# 8. PROJECT BUDGET

Admin enters:

* Approved Budget
* Budget Remarks

The approved budget becomes the baseline for financial monitoring.

Later:

Finance & Accounting records expenses against this budget.

Do not allow ordinary Project Personnel to change the approved budget.

---

# 9. PROJECT TIMELINE

Fields:

* Planned Start Date
* Target Completion Date
* Timeline Remarks

Automatically calculate:

* Project Duration
* Days elapsed
* Days remaining
* Delay days

Validate:

* Start date cannot be after target completion date.

Timeline status:

* Not Started
* On Schedule
* Approaching Deadline
* Delayed
* Completed

---

# 10. PROJECT ASSIGNMENT

Admin assigns Project Personnel.

A project may have one or multiple assigned personnel.

Store:

* User
* Position Type
* Responsibility
* Assignment Date
* Remarks

Position types:

* OIC
* Staff
* Branch Manager
* Foreman
* Contractor

The assignment determines which projects the Project Personnel can access.

---

# 11. PROJECT DOCUMENTS

Support:

* Approved Design
* Project Plan
* Contract
* Quotations
* Accomplishment Reports
* Progress Reports
* Progress Photos
* Other Supporting Documents

Each document stores:

* Project ID
* Document Type
* Document Name
* File Path
* File Type
* File Size
* Version
* Description
* Uploaded By
* Upload Date

Use Laravel Storage.

Validate:

* File type
* File size
* Filename

Do not allow unauthorized users to replace protected documents.

---

# 12. APPROVED DESIGN MANAGEMENT

The approved design is the official project design.

Store:

* Design name
* Version
* Description
* File
* Upload date

Project Personnel cannot replace the approved design.

If a revised design is needed, store it as a new version rather than deleting the previous design.

Maintain document history.

---

# 13. PROJECT DASHBOARD

Create a professional Admin dashboard.

Display:

* Total Projects
* Registered Projects
* Ongoing Projects
* Completed Projects
* Delayed Projects
* On-Hold Projects
* Total Approved Budget
* Total Project Expenses
* Remaining Budget
* Projects Near Budget Limit
* Projects Over Budget

Charts:

* Projects by status
* Project completion
* Budget utilization
* Projects by category
* Monthly project activity

Create a:

`Projects Requiring Attention`

section.

Examples:

* Delayed project
* Deadline approaching
* Budget approaching limit
* Budget exceeded
* No recent progress update
* Missing required document

---

# 14. PROJECT PERSONNEL DASHBOARD

Project Personnel should see ONLY assigned projects.

Display:

* Assigned Projects
* Ongoing Projects
* Completed Projects
* Delayed Projects
* Upcoming Deadlines

For each assigned project:

* Project name
* Completion percentage
* Timeline
* Budget
* Latest update
* Required action

---

# 15. FINANCE & ACCOUNTING DASHBOARD

Display:

* Total Approved Budget
* Total Expenses
* Total Remaining Budget
* Overall Budget Utilization
* Projects Approaching Budget Limit
* Projects Over Budget
* Supplemental Budgets

Provide financial charts.

---

# 16. PROJECT DETAILS

Create a comprehensive Project Details page.

Use tabs:

## Overview

* Project code
* Title
* Description
* Category
* Type
* Location
* Objective
* Status

## Timeline

* Start date
* Target completion
* Actual start
* Actual completion
* Duration
* Days remaining
* Delay days
* Completion percentage

## Budget

* Approved budget
* Supplemental budget
* Total available budget
* Total expenses
* Remaining budget
* Utilization percentage
* Budget status

## Progress

* Progress updates
* Accomplishments
* Completion percentage
* Activities
* Issues
* Delays

## Materials

* Materials used
* Quantity
* Unit
* Unit cost
* Total cost
* Date used
* Supplier

## Personnel

* Assigned personnel
* Position
* Responsibility

## Contractor

* Contractor/provider
* Contact information
* Project history

## Documents

* Approved design
* Project plan
* Contracts
* Quotations
* Reports
* Photos
* Other documents

## Activity Log

Display important project actions.

---

# 17. PROJECT PROGRESS

Project Personnel can submit progress updates.

Fields:

* Project
* Date
* Completion Percentage
* Accomplishment Description
* Activities Completed
* Activities Remaining
* Issues
* Remarks

Allow uploads:

* Progress photos
* Progress reports
* Supporting documents

Every update must be stored as a separate record.

Do NOT overwrite historical progress.

---

# 18. MATERIAL TRACKING

Project Personnel can record materials.

Fields:

* Material
* Description
* Quantity
* Unit
* Unit Cost
* Total Cost
* Date Used
* Supplier
* Remarks

Automatically calculate:

`Quantity × Unit Cost = Total Cost`

Finance & Accounting can view material costs as part of project expenses where applicable.

---

# 19. FINANCE & BUDGET MANAGEMENT

Finance & Accounting records project expenses.

Expense fields:

* Project
* Expense Date
* Expense Category
* Description
* Amount
* Reference Number
* Payee/Supplier
* Supporting Document
* Remarks

Calculate:

### Total Available Budget

`Approved Budget + Supplemental Budget`

### Total Expenses

Sum of all valid project expenses.

### Remaining Budget

`Total Available Budget - Total Expenses`

### Budget Utilization

`Total Expenses / Total Available Budget × 100`

---

# 20. BUDGET STATUS

Automatically calculate:

### Below 80%

`Within Budget`

### 80%–99%

`Approaching Budget Limit`

### 100%+

`Budget Exceeded`

If the project exceeds the available budget:

* Display warning
* Notify Admin/CEO
* Notify Finance & Accounting
* Record audit event

Do NOT automatically increase the budget.

---

# 21. SUPPLEMENTAL BUDGET

Allow authorized Admin/Finance users to record supplemental budgets.

Fields:

* Project
* Amount
* Reason
* Reference
* Date
* Supporting Document
* Remarks

Do not overwrite the original approved budget.

Keep a complete history.

---

# 22. CONTRACTOR / PROVIDER MANAGEMENT

Create contractor/provider records.

Fields:

* Company/Provider Name
* Contact Person
* Contact Number
* Email
* Address
* Registration Information
* Status
* Remarks

Track:

* Previous projects
* Project values
* Project dates
* Project history

---

# 23. THREE-PROVIDER REQUIREMENT

If there is no previous provider/contractor history for the required project procurement process, the system should support recording at least **three provider quotations**.

Record:

* Provider
* Quotation amount
* Quotation date
* Quotation document
* Remarks

The system may compare quotations.

IMPORTANT:

The system must NOT automatically choose or rank a provider.

It should only organize the information for management.

---

# 24. ₱50,000 PROJECT THRESHOLD

The project budget threshold should be configurable in Settings.

Default:

`₱50,000`

Create:

`Project Registration Threshold`

The system can use this value to identify projects meeting the organization's configured threshold.

Do NOT hard-code ₱50,000 into business logic.

Admin should be able to change the threshold from Settings.

---

# 25. TIMELINE MONITORING

Automatically monitor:

* Planned start
* Target completion
* Actual start
* Actual completion
* Completion percentage
* Days remaining
* Delay days

If target completion date has passed and the project is not completed:

Set timeline status to:

`Delayed`

Notify the appropriate users.

---

# 26. NOTIFICATIONS

Create an in-system notification center.

Use a notification bell icon.

Notifications include:

* Project assigned
* Project progress updated
* Project approaching deadline
* Project delayed
* Budget approaching limit
* Budget exceeded
* Supplemental budget recorded
* New document uploaded
* Project completed

Use Laravel Notifications.

---

# 27. REPORTS

Create PDF reports.

## Project Status Report

Include:

* Project information
* Status
* Timeline
* Completion
* Accomplishments
* Issues

## Budget Monitoring Report

Include:

* Approved budget
* Supplemental budget
* Total available
* Expenses
* Remaining budget
* Utilization
* Budget status

## Project Accomplishment Report

Include:

* Project
* Accomplishments
* Completion
* Dates
* Supporting information

## Delayed Projects Report

## Project Summary Report

## Contractor/Provider History Report

## Material Usage Report

Allow filters:

* Date
* Project
* Category
* Contractor
* Assigned personnel
* Status
* Budget range

Allow:

* PDF export
* Excel export

---

# 28. SEARCH AND FILTERING

Implement global search.

Search:

* Project code
* Project title
* Location
* Contractor
* Assigned personnel

Filters:

* Status
* Category
* Project type
* Date range
* Budget range
* Contractor
* Assigned personnel

---

# 29. AUDIT LOG

Create a complete audit trail.

Record:

* User
* Action
* Module
* Record
* Old values
* New values
* Timestamp
* IP address where appropriate

Examples:

`Admin registered project BMPC-PRJ-2026-0001`

`Admin assigned Juan Dela Cruz to project BMPC-PRJ-2026-0001`

`Project Personnel updated project progress from 40% to 55%`

`Finance recorded expense of ₱25,000`

`Finance recorded supplemental budget of ₱50,000`

Do not allow normal users to delete audit records.

---

# 30. ROLE-BASED ACCESS CONTROL

Implement authorization using Laravel Policies/Gates.

Never rely only on hiding buttons.

### Admin / CEO

Full project management access.

### Project Personnel

Only assigned projects.

### Finance & Accounting

Financial and budget-related access.

Unauthorized users must receive HTTP 403 where appropriate.

---

# 31. DATABASE DESIGN

Create normalized migrations.

At minimum:

## users

* id
* name
* email
* password
* role
* position_type
* timestamps

## projects

* id
* project_code
* title
* description
* category_id
* project_type
* location
* objective
* approved_budget
* planned_start_date
* target_completion_date
* actual_start_date
* actual_completion_date
* status
* remarks
* created_by
* timestamps
* softDeletes

## project_categories

* id
* name
* description
* status
* timestamps

## project_assignments

* id
* project_id
* user_id
* position_type
* responsibility
* assignment_date
* remarks
* timestamps

## project_documents

* id
* project_id
* document_type
* document_name
* file_path
* file_type
* file_size
* version
* description
* uploaded_by
* timestamps

## project_progress

* id
* project_id
* user_id
* progress_percentage
* progress_date
* accomplishments
* activities_completed
* activities_remaining
* issues
* remarks
* timestamps

## project_materials

* id
* project_id
* material_name
* description
* quantity
* unit
* unit_cost
* total_cost
* date_used
* supplier
* remarks
* timestamps

## project_expenses

* id
* project_id
* expense_date
* category
* description
* amount
* reference_number
* payee
* document_path
* remarks
* created_by
* timestamps

## supplemental_budgets

* id
* project_id
* amount
* reason
* reference
* approval_date
* document_path
* remarks
* created_by
* timestamps

## contractors

* id
* name
* contact_person
* contact_number
* email
* address
* registration_information
* status
* remarks
* timestamps

## contractor_quotations

* id
* project_id
* contractor_id
* quotation_amount
* quotation_date
* document_path
* remarks
* timestamps

## project_contractors

* id
* project_id
* contractor_id
* role
* contract_amount
* start_date
* end_date
* remarks
* timestamps

## notifications

Use Laravel's notification system.

## audit_logs

* id
* user_id
* action
* module
* record_id
* description
* old_values
* new_values
* ip_address
* timestamps

## system_settings

* id
* key
* value
* description
* timestamps

---

# 32. DATABASE RELATIONSHIPS

Implement proper Eloquent relationships.

Project:

* belongsTo Category
* belongsTo User as creator
* hasMany Assignments
* hasMany Documents
* hasMany Progress
* hasMany Materials
* hasMany Expenses
* hasMany Supplemental Budgets
* hasMany Quotations
* belongsToMany Contractors where appropriate

User:

* hasMany Created Projects
* hasMany Project Assignments
* hasMany Progress Updates

---

# 33. PROJECT CODE

Automatically generate:

`BMPC-PRJ-YEAR-NUMBER`

Example:

`BMPC-PRJ-2026-0001`

Ensure:

* Unique
* Sequential
* Year-based
* Automatically generated

Do not rely on user input for uniqueness.

---

# 34. PROJECT DOCUMENT VERSIONING

For documents that can have revisions:

* Keep previous versions.
* Mark one version as current.
* Never silently delete historical versions.
* Store uploader and timestamp.

Approved design must maintain version history.

---

# 35. ADMIN NAVIGATION

Use:

Dashboard

Projects

* All Projects
* Register Project

Project Monitoring

Budget & Finance

Contractors / Providers

Documents

Reports

Users

Notifications

Audit Logs

Settings

Display navigation based on permissions.

---

# 36. PROJECT PERSONNEL NAVIGATION

Use:

Dashboard

My Projects

Notifications

Profile

They should not see Admin-only modules.

---

# 37. FINANCE NAVIGATION

Use:

Dashboard

Projects

Budget Monitoring

Expenses

Supplemental Budgets

Reports

Notifications

Profile

---

# 38. UI/UX DESIGN

The interface must have a **clean, modern iOS-inspired design**.

Do NOT use emojis anywhere.

Use professional SVG icons through:

**Lucide Icons**

Examples:

* LayoutDashboard
* Folder
* FileText
* Users
* User
* Calendar
* Wallet
* Calculator
* Chart
* Bell
* Settings
* Search
* Plus
* Eye
* Pencil
* Upload
* Download
* Archive
* Check
* AlertTriangle
* Clock
* Building
* HardHat

Design principles:

* Clean
* Minimal
* Professional
* Spacious
* Modern
* Responsive
* Accessible

Use:

* Rounded cards
* Subtle borders
* Soft shadows
* Clean typography
* Clear status badges
* Consistent spacing
* Modern tables
* Clean forms
* Modal dialogs
* Slide-over panels where appropriate
* Smooth transitions
* Responsive layouts

Avoid:

* Emojis
* Excessive colors
* Excessive gradients
* Clutter
* Huge decorative elements
* Old-fashioned UI
* Excessive animations

---

# 39. STATUS BADGES

Use clean text + icons.

Examples:

`Registered`
`Ongoing`
`On Hold`
`Completed`
`Cancelled`

Timeline:

`On Schedule`
`Approaching Deadline`
`Delayed`

Budget:

`Within Budget`
`Approaching Limit`
`Exceeded`

Do not use emojis.

---

# 40. RESPONSIVE DESIGN

Support:

* Desktop
* Laptop
* Tablet
* Mobile

Mobile behavior:

* Collapsible sidebar
* Responsive tables
* Stacked cards
* Single-column forms
* Mobile-friendly modals
* Touch-friendly controls

---

# 41. VALIDATION

Use Laravel Form Requests.

Examples:

* Budget must be numeric.
* Budget cannot be negative.
* Completion percentage must be 0–100.
* Start date cannot exceed target completion date.
* Expense amount must be positive.
* Required project information cannot be empty.
* Files must have valid extensions and sizes.
* Project code must be unique.

---

# 42. SECURITY

Implement:

* Authentication
* Password hashing
* CSRF protection
* Authorization
* Policies
* Form validation
* Secure file storage
* Protected routes
* SQL injection prevention
* XSS protection
* Session security
* Audit logging

Do not expose sensitive server/database information.

---

# 43. SETTINGS

Create system settings for:

* Organization Name
* Organization Logo
* Currency
* Project Registration Threshold
* Budget Warning Threshold
* Project Categories
* Document Categories
* Notification Settings

Default project registration threshold:

`₱50,000`

Default budget warning threshold:

`80%`

Make these configurable.

---

# 44. SEED DATA

Create fictional development data.

### Admin

Name:
`System Administrator`

Role:
`admin`

### Project Personnel

Name:
`Juan Dela Cruz`

Role:
`project_personnel`

Position:
`Contractor`

### Project Personnel

Name:
`Maria Santos`

Role:
`project_personnel`

Position:
`Foreman`

### Finance

Name:
`Finance Officer`

Role:
`finance_accounting`

Create several fictional projects with:

* Different statuses
* Different budgets
* Different timelines
* Different progress
* Different expenses
* Different contractors
* Different materials

Include test cases for:

* Normal budget
* Approaching budget
* Exceeded budget
* Delayed project
* Completed project

Do not use real personal information.

---

# 45. ERROR HANDLING

Create professional error states.

Examples:

* Empty project list
* No search results
* Project not found
* Unauthorized access
* Failed upload
* Invalid file
* Validation errors
* Database error

Use clean UI notifications.

Avoid browser `alert()`.

---

# 46. TESTING

Create and run tests for:

### Authentication

* Login
* Logout
* Unauthorized access

### Project

* Create
* Read
* Update
* Archive
* Search
* Filter

### Assignment

* Assign personnel
* Remove assignment
* Access control

### Budget

* Expense calculation
* Remaining budget
* Utilization percentage
* Budget warning
* Budget exceeded

### Timeline

* Duration
* Days remaining
* Delay calculation
* Completion

### Documents

* Upload
* Download
* Authorization
* Validation

### Progress

* Create progress update
* Historical records
* Completion percentage

### Authorization

Verify:

* Project Personnel cannot access another person's project.
* Project Personnel cannot modify approved budget.
* Finance cannot modify project design.
* Finance cannot assign personnel.
* Only Admin can manage users/settings.

---

# 47. DEVELOPMENT INSTRUCTIONS

Before making changes:

1. Inspect the existing project directory.
2. Determine whether Laravel is already installed.
3. Inspect existing migrations.
4. Inspect existing models.
5. Inspect existing authentication.
6. Inspect routes.
7. Inspect Blade views.
8. Inspect database configuration.
9. Reuse existing working components where appropriate.
10. Do not destroy existing functionality.

Then implement the system incrementally.

Recommended order:

### Phase 1

Database and authentication.

### Phase 2

Roles and authorization.

### Phase 3

Project registration.

### Phase 4

Project assignment.

### Phase 5

Project details and documents.

### Phase 6

Project progress monitoring.

### Phase 7

Materials.

### Phase 8

Contractors/providers.

### Phase 9

Finance and budget monitoring.

### Phase 10

Timeline monitoring and notifications.

### Phase 11

Reports.

### Phase 12

Audit logs.

### Phase 13

UI/UX refinement.

### Phase 14

Testing and bug fixing.

---

# 48. CRITICAL BUSINESS RULES

These rules must be followed exactly:

1. There are only THREE system roles:

   * Admin/CEO
   * Project Personnel
   * Finance & Accounting

2. Admin and CEO are one role.

3. Board is completely outside the system.

4. There is NO Board account or Board module.

5. Projects are already approved before they enter the system.

6. Admin registers the approved project.

7. Admin assigns Project Personnel.

8. Project Personnel can update only projects assigned to them.

9. OIC, Staff, Branch Manager, Foreman, and Contractor are position types under Project Personnel, not separate system roles.

10. Project Personnel cannot change approved budget.

11. Finance & Accounting tracks project expenses and budget utilization.

12. Original approved budget must never be overwritten by supplemental budget.

13. Supplemental budget must be stored separately.

14. Approved designs must have version/history protection.

15. Progress updates must be historical records.

16. Project delays must be automatically identifiable.

17. Budget overruns must be automatically identifiable.

18. Contractor/provider history must be maintained.

19. The three-provider requirement should be supported when there is no provider history.

20. The system must not automatically choose a contractor/provider.

21. The ₱50,000 threshold must be configurable.

22. Important system actions must be recorded in the audit log.

23. Unauthorized users must not access restricted routes even by manually entering URLs.

---

# 49. FINAL ACCEPTANCE TEST

The system should successfully support this complete workflow:

### STEP 1

Admin logs in.

### STEP 2

Admin opens:

`Projects → Register Project`

### STEP 3

Admin enters an already-approved project:

* Project title
* Description
* Category
* Type
* Location
* Objective
* Approved budget
* Timeline

### STEP 4

Admin uploads:

* Approved design
* Project plan
* Supporting documents

### STEP 5

Admin assigns:

`Project Personnel`

with a position such as:

`Contractor`

### STEP 6

Admin registers the project.

System automatically:

* Generates project code
* Sets status to Registered
* Saves all information
* Saves assignments
* Saves documents
* Creates audit log

### STEP 7

Project Personnel logs in.

They can see the assigned project.

### STEP 8

Project Personnel updates:

* Progress
* Accomplishment
* Materials
* Issues
* Photos
* Reports

### STEP 9

Finance & Accounting logs in.

They record:

* Project expenses
* Financial transactions
* Supplemental budget if applicable

### STEP 10

System automatically calculates:

* Total available budget
* Total expenses
* Remaining budget
* Budget utilization
* Budget status
* Timeline status

### STEP 11

Admin monitors the entire project.

### STEP 12

System generates:

* Project reports
* Budget reports
* Progress reports
* Delayed project reports
* Contractor history
* Material reports

---

# 50. FINAL REQUIREMENT

Do not create mock functionality.

Do not leave buttons as placeholders.

Do not create fake data as a substitute for actual functionality.

Every feature included in this specification must be connected to the database and functional.

Use realistic validation and authorization.

Keep the code clean and maintainable.

The final application should look and behave like a professional internal business application, with a clean iOS-inspired interface and no emojis.

After implementation:

1. Run migrations.
2. Run seeders.
3. Run tests.
4. Start the application.
5. Test the major workflows manually.
6. Fix errors.
7. Check responsive layouts.
8. Check authorization.
9. Check database relationships.
10. Check file uploads.
11. Check calculations.
12. Check reports.

Finally provide a concise development summary containing:

* Technologies used
* Architecture
* Database tables
* User roles
* Major modules
* Routes
* Default development accounts
* How to install
* How to run
* How to run migrations
* How to run seeders
* How to run tests
* Important assumptions
* Remaining future enhancements
