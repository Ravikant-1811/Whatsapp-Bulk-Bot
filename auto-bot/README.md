🚀 WhatsApp Bulk Automation System
PHP + MySQL + Wasender API + Excel Import

A powerful WhatsApp bulk messaging automation system with:

Excel contact import

MySQL database storage

Daily rate limiting

Human-like sending delay

Poll-based engagement

Webhook reply detection

Automated follow-ups

Full message logging

Resume-safe worker

📌 Features
📥 Contact Management

Import contacts from Excel (.xlsx)

Automatic deduplication

Stored in MySQL database

Status tracking:

pending

sent

failed

📤 Smart Bulk Messaging

Daily limit (default: 250/day)

Random delay (10–25 seconds)

Smart break after 25 messages (3–7 minutes)

Retry failed contacts (max 3 retries)

Auto resume (safe worker)

Lock system to prevent duplicate sending

📊 Poll-Based Engagement

Sends WhatsApp Poll:

Actively Investing

Comfortable

When user votes:

Webhook captures poll response

Updates contact status

Sends automated follow-up message

Logs interaction in database

🔁 Two-Way Automation

Webhook listener:

Detects poll result

Extracts voter number

Updates contact status

Sends contextual follow-up

Logs reply in reply_logs

🏗️ System Architecture
Excel → MySQL → CLI Worker → Wasender API → WhatsApp
Webhook → Detect Vote → Update Status → Follow-up

🛠️ Requirements

PHP 8+

Composer

XAMPP (MySQL + Apache)

Wasender API account

ngrok (for local webhook testing)

⚙️ Installation Guide
1️⃣ Clone Repository
git clone https://github.com/ziservices/Whatsapp-Bulk-Bot.git

cd Whatsapp-Bulk-Bot

2️⃣ Install Dependencies
composer install

3️⃣ Create Database

Create database in phpMyAdmin:

whatsapp_campaign

4️⃣ Create Tables
contacts
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    number VARCHAR(20) UNIQUE,
    business_type VARCHAR(255),
    status VARCHAR(50) DEFAULT 'pending',
    retry_count INT DEFAULT 0,
    last_sent_at DATETIME NULL,
    locked_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

message_logs
CREATE TABLE message_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id INT,
    phone VARCHAR(20),
    message TEXT,
    response LONGTEXT,
    status VARCHAR(50),
    error TEXT,
    created_at DATETIME,
    FOREIGN KEY (contact_id) REFERENCES contacts(id)
);

reply_logs
CREATE TABLE reply_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id INT,
    direction VARCHAR(50),
    message TEXT,
    button_id VARCHAR(255),
    created_at DATETIME
);

5️⃣ Configure Database

Edit:

config/db.php

$host = '127.0.0.1';
$db   = 'whatsapp_campaign';
$user = 'root';
$pass = '';

📥 Add Excel File

Place your Excel file inside project root:

Test.xlsx

Required columns:

name | number | business_type (optional)

▶️ Run Messaging Worker
php send_msg_name_twoway.php


Worker will:

Sync Excel to MySQL

Apply daily limit

Send poll message

Log responses

Retry safely

Apply human-like delay

🌐 Webhook Setup (Local Development)

Start ngrok

ngrok http 80

Example output:

https://abcd1234.ngrok-free.app → http://localhost:80

Set Wasender webhook URL to:

https://abcd1234.ngrok-free.app/webhook.php

🧠 Automation Logic
If user selects:
✅ Actively Investing

Status → interested

Send strategy call message

💤 Comfortable

Status → not_interested

Send nurture message

📂 Project Structure
/config
    db.php

import_contacts.php

send_msg_name_twoway.php

webhook.php

Test.xlsx

composer.json

🔒 Safety & Limits

250 messages per day

Randomized delay

Retry logic

Row locking

Poll interaction tracking

Designed to reduce spam risk and improve engagement.

🚀 Future Enhancements

Admin Dashboard (Tailwind UI)

Campaign manager

Pause / Resume button

Media support

Scheduled campaigns

Multi-account support

AI lead scoring

CRM integration

👨‍💻 Created By

Preet Darji

Zeus Infinity Services

https://zeusinfinityservices.com
