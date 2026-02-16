# FlowManager 🚀

FlowManager is a powerful, event-driven workflow and approval management system built with Laravel. It enables organizations to define complex approval hierarchies and automate business processes with a focus on performance, scalability, and observability.

---

## 🌟 Key Features

-   **Dynamic Workflow Engine**: Flexible multi-step approval flows (Manager -> HR -> Finance, etc.).
-   **Hierarchical Approvals**: Automatic determination of approvers based on organizational structure (Direct Manager, Team Manager, or HR Fallbacks).
-   **Event-Driven Architecture**: Fully decoupled system using Laravel Events and Listeners for asynchronous processing.
-   **Queued Email Notifications**: Instant API responses with background email delivery to requesters and approvers.
-   **Scalable Document Management**: Fast, non-blocking document uploads to **AWS S3** via background queued jobs.
-   **Monitoring & Observability**: Integrated with **Laravel Horizon** for Redis queue management and **Laravel Telescope** for deep application debugging.
-   **Role-Based Access Control**: Secure endpoints restricted by roles (Manager, HR, Employee).
-   **Customizable Workflow Steps**: Support for sequential ordering and role-based permissions at every step.

---

## 🛠️ Technical Stack

-   **Backend**: Laravel (PHP)
-   **Database**: PostgreSQL / MySQL
-   **Cache & Queue**: Redis
-   **Storage**: AWS S3
-   **Monitoring**: 
    -   **Laravel Horizon**: Real-time Redis queue dashboard.
    -   **Laravel Telescope**: Application debugging and performance monitoring.
-   **Auth**: Laravel Sanctum / Spatie Laravel Permission

---

## 📊 Monitoring Dashboards

### Laravel Horizon
Monitor your Redis queues, job throughput, and failed jobs in real-time.
- **Route**: `/horizon`

### Laravel Telescope
The ultimate debugger for Laravel applications. Track requests, database queries, emails, and exceptions.
- **Route**: `/telescope`

---

## 🛡️ Architecture Highlights

### Non-Blocking File Uploads
FlowManager uses a two-step upload process to ensure maximum performance:
1.  **Local Cache**: Files are stored locally in a temporary directory immediately.
2.  **Queued S3 Sync**: The `DocumentsUploaded` event triggers a background job that streams files to S3 and cleans up local storage.

### Event-Driven Notifications
Every stage of the workflow (Creation, Approval, Rejection) is handled by events, ensuring the core business logic remains clean and the UI remains snappy.

---

## 🚀 Getting Started

1.  **Clone the repository**:
    ```bash
    git clone https://github.com/your-username/FlowManager.git
    ```
2.  **Install dependencies**:
    ```bash
    composer install
    npm install
    ```
3.  **Configure Environment**:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
4.  **Database & Migration**:
    ```bash
    php artisan migrate --seed
    ```
5.  **Run Workers**:
    ```bash
    php artisan horizon
    ```

---

## 📝 License

This project is open-sourced software licensed under the [MIT license](LICENSE).
