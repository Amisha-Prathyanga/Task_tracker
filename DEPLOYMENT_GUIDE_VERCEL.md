# Deployment Guide for Vercel

This guide outlines the steps to deploy your PHP Task Tracker application to Vercel.

## Prerequisites

1.  **Vercel Account**: [Sign up here](https://vercel.com/signup).
2.  **Cloud Database**: Since Vercel is serverless, you cannot use a local MySQL database (localhost). You need a cloud-hosted MySQL database.
    - **Options**: [Aiven](https://aiven.io/mysql) (Free tier available), [PlanetScale](https://planetscale.com/), or any remote MySQL server.
3.  **Vercel CLI** (Optional but recommended): Install via npm: `npm i -g vercel`.

## Step 1: Prepare Your Database

1.  **Create a Cloud Database**: Sign up for one of the services mentioned above and create a new MySQL database service.
2.  **Import Your Data**:
    - Export your local `task_tracker` database to a `.sql` file (using phpMyAdmin or `mysqldump`).
    - Connect to your new cloud database using a tool like MySQL Workbench, DBeaver, or phpMyAdmin (if provided by the host).
    - Import your `.sql` file to the cloud database.
3.  **Get Credentials**: Note down the following details from your cloud database provider:
    - Host (e.g., `mysql-service.aivencloud.com`)
    - Port (e.g., `12345`)
    - Username
    - Password
    - Database Name

## Step 2: Configuration (Already Done)

We have already updated your `config.php` to accept Environment Variables and created a `vercel.json` file to tell Vercel how to run your PHP code.

- `config.php`: Updated to look for `DB_HOST`, `DB_USER`, etc.
- `vercel.json`: Configured to use `vercel-php@0.6.0` runtime.

## Step 3: Deploy to Vercel

### Option A: Using Vercel CLI (Recommended for quick test)

1.  Open your terminal/command prompt in the project folder:
    ```bash
    cd c:\xampp\htdocs\Task_tracker
    ```
2.  Run the deploy command:
    ```bash
    vercel
    ```
3.  Follow the prompts:
    - Set up and deploy? **Y**
    - Which scope? (Select your account)
    - Link to existing project? **N**
    - Project name? **task-tracker** (or your choice)
    - In which directory? **./** (Just press Enter)
    - Auto-detect Project Settings? **y** (It might say "No framework detected", that's fine override if needed, but usually default is okay since we hava vercel.json).
    - **IMPORTANT**: If it asks for Build settings, you can usually skip defaults.

4.  **Set Environment Variables**:
    - Go to your Vercel Dashboard -> Select your Project -> **Settings** -> **Environment Variables**.
    - Add the following variables (Key: Value):
      - `DB_HOST`: Your cloud database host
      - `DB_USER`: Your cloud database username
      - `DB_PASS`: Your cloud database password
      - `DB_NAME`: Your cloud database name
      - `DB_PORT`: Your cloud database port (default 3306)

5.  **Redeploy**: After adding variables, you might need to redeploy for them to take effect (`vercel --prod`).

### Option B: Using GitHub/GitLab

1.  Push your code to a GitHub repository.
2.  Login to Vercel and click "Add New..." -> "Project".
3.  Import your GitHub repository.
4.  In the "Environment Variables" section, add the DB credentials listed in Step 3.
5.  Click **Deploy**.

## IMPORTANT: File Upload Limitation

Vercel is a **Serverless Platform**, which means it has a **Read-Only Filesystem**.

- **The Problem**: Code that tries to save files to the local server (like `uploads/job_applications`) **WILL NOT WORK**. The files will either fail to save or disappear immediately after the request finishes.
- **The Solution**: To support file uploads on Vercel, you must modify your application to upload files to an external storage service like **AWS S3**, **Google Cloud Storage**, or **Cloudinary**.
- **Current Status**: The app will deploy and run, but image uploads for Job Applications will fail.
