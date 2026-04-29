# Taskora Backend API 🚀

Taskora is a comprehensive multi-tenant SaaS application backend for task and project management, built with **Laravel 11**. It provides a robust RESTful API that powers the Taskora Flutter mobile application.

## 🌟 Key Features

### 🔐 Authentication & Security
- **Laravel Sanctum** for stateless API token authentication.
- **OTP-based Password Reset:** Secure 6-digit email OTP system with retry limits, expiration, and branded email templates.
- **Rate Limiting:** Built-in protection against brute force attacks on Auth and OTP endpoints.

### 🏢 Multi-Tenant Workspaces
- Users can create and manage Workspaces.
- **Roles & Permissions:** Hierarchy support (`owner`, `admin`, `member`, `reviewer`, `tester`).
- **Invitation System:** Securely invite new members via email links with expiration limits.
- Global middleware (`EnsureWorkspace`) to ensure users are acting within an active workspace context.

### 📁 Projects & Tasks Management
- CRUD operations for Projects.
- **Advanced Task Workflow:**
  - Status tracking: `pending`, `in_progress`, `review`, `done`.
  - Task priorities and due dates.
  - Granular task assignment to specific users.
  - Task commenting system for team collaboration.
- **Activity Logging:** Tracks all major actions (creation, updates, status changes) for a complete audit trail.

### 🔔 Bi-Directional Notification System
- **Push Notifications:** Centralized dispatching system for Device Tokens.
- Smart notification routing:
  - Admins are notified of all critical workspace changes.
  - Users are notified of task assignments and status updates relevant to them.
- **Deduplication Engine:** Custom lock mechanism (fingerprinting) to prevent duplicate notifications from firing within short intervals.
- In-App database notifications for dashboard alerts with Unread Count badges.

### 📊 Analytics & Dashboard
- **Workspace Dashboard Stats:** Real-time metrics on total projects, tasks, and completion rates.
- **Employee Stats:** Identifies the most loaded employees, tracks individual performance, and generates team reports.
- Real-time activity feeds for admins.

## 🛠️ Technology Stack
- **Framework:** Laravel 11
- **Database:** SQLite (Configured for easy dev/prod transition to MySQL/PostgreSQL)
- **Authentication:** Laravel Sanctum
- **Mailing:** SMTP / Mailtrap for OTP and Invites
- **API Architecture:** RESTful JSON API

## 🚀 Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yousseframdan72006/taskora_backend.git
   cd taskora_backend
   ```

2. **Install Dependencies:**
   ```bash
   composer install
   ```

3. **Environment Setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database Migration:**
   ```bash
   php artisan migrate --seed
   ```

5. **Run the Development Server:**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```
   *(Running on `0.0.0.0` allows mobile emulators and physical devices on the same network to connect).*

## 📖 API Architecture Highlights
- **Strict Validation:** Custom FormRequests for all endpoints.
- **Event-Driven:** Uses Laravel Events and Listeners (`TaskStatusChanged`, `TaskAssigned`, `ProjectCreated`) to decouple business logic from side effects (like notifications).
- **Service Pattern:** Complex logic (like OTP generation, validation, and Push Notification deduplication) is isolated in Dedicated Services and Jobs.
