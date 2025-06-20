<p align="center">
  <img alt="symfony logo" src="https://symfony.com/images/logos/header-logo.svg" width="300">
</p>

<h1 align="center">Bloom - Symfony Application</h1>

# 🎓 Bloom - Healthcare Platform

Welcome to the GitHub repository of **Bloom**, a🧠 HealthCare AI Platform passionately created by the **Torvalds Troopers** team 🧠.

---

## 📚 Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Technologies](#technologies)
- [Team](#team-brainiacs)
- [Project Structure](#project-structure)
- [Contributions](#contributions)
- [License](#license)

---

## Project Overview

> **Bloom** is a 

---



## Technologies

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-000000?style=for-the-badge&logo=symfony&logoColor=white)
![Composer](https://img.shields.io/badge/Composer-885630?style=for-the-badge&logo=composer&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Doctrine](https://img.shields.io/badge/Doctrine-FF6C37?style=for-the-badge)
![Twig](https://img.shields.io/badge/Twig-009E5B?style=for-the-badge&logo=twig&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Bootstrap](https://img.shields.io/badge/Bootstrap-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![GitHub](https://img.shields.io/badge/Hosted_on-GitHub-181717?style=for-the-badge&logo=github)


---

## Team Torvalds Troopers

| Name               | Role             |
|--------------------|------------------|
| 👩‍💻 Mehdi Bani     | Developer         |
| 👩‍💻 Hedi Brahmi   | Developer         |
| 👩‍💻 Ahmed Bettaieb     | Developer         |
| 👩‍💻 Ahmed Karray    | Developer         |
| 👩‍💻 Hidaya Hanafi     | Developer         |
| 👨‍💻 Strine Wannes | Developer         |

---

## Project Structure

```bash
MentorSymf/
├── src/
│   ├── controller/        #  Controllers
│   ├── Command/           # command
│   ├── Entity/            # Entity Views
│   ├── Form/              # Form
│   ├── Repository/        # Repository
│   ├── Service/           # service           
│   └── Security/          #  security
├── template/
│   ├── back/              # back
│   ├── admin/          # calender
│   ├── front/             # Front
│   └── user/              # user
├── .env                   # .env
└── README.md              # Documentation

```

---

## Installation
Read the steps to install and run this Symfony project in your region:

### 1. Clone the repository
git clone 
cd my_project

### 2. Install PHP dependencies
composer install

### 3. Create and configure `.env.local`
cp .env .env.local
# Edit the .env.local file:
# DATABASE_URL="mysql://username:password@127.0.0.1:3306/final_bd"


### 4. Create the database
php bin/console doctrine:database:create

### 5. Generate the database schema
php bin/console doctrine:schema:update --force

### 6. Load test data (fixtures)
php bin/console doctrine:fixtures:load

### 7. Start the server
# Option 1 (Symfony CLI):
symfony serve
# Then visit: https://localhost:8000

# Option 2 (PHP built-in server):
php -S localhost:8000 -t public/

## Prerequisites & Tools

# Recommended Editor: VS Code
# Download: https://code.visualstudio.com/

# PHP (v8.0+ required)
# Windows: Install from https://www.php.net/downloads.php or via XAMPP
# macOS:
brew install php
# Linux (Ubuntu/Debian):
sudo apt update
sudo apt install php php-cli php-mbstring php-xml php-curl php-zip php-mysql

# Local Server: XAMPP
# Windows/macOS/Linux: https://www.apachefriends.org/

# Composer (Dependency manager)
# Windows: https://getcomposer.org/
# macOS/Linux:
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# MySQL Database
# Included with XAMPP
# macOS:
brew install mysql
# Linux (Ubuntu/Debian):
sudo apt update
sudo apt install mysql-server

## Required APIs & Bundles

# 1. Google API Client
composer require google/apiclient
composer require google-gemini-php/symfony

# 2. Google Cloud Vision
composer require google/cloud-vision

# 3. Google Gemini (again)
composer require google-gemini-php/symfony

# 4. Stripe (payments)
composer require stripe/stripe-php

# 5. Twilio (messaging & communication)
composer require twilio/sdk

# 6. Doctrine ORM
composer require doctrine/orm

# 7. Doctrine Bundle
composer require doctrine/doctrine-bundle

# 8. Doctrine Migrations Bundle
composer require doctrine/doctrine-migrations-bundle

# 9. Symfony Security Bundle
composer require symfony/security-bundle

# 10. Lexik JWT Authentication
composer require lexik/jwt-authentication-bundle

# 11. Symfony Form
composer require symfony/form

# 12. Symfony Validator
composer require symfony/validator

# 13. ReCaptcha Bundle
composer require victor-prdh/recaptcha-bundle

# 14. VichUploaderBundle (file uploads)
composer require vich/uploader-bundle

# 15. Omnipay Stripe (advanced Stripe integration)
composer require omnipay/stripe

# 16. KnpSnappyBundle (PDF/HTML to image generator)
composer require knplabs/knp-snappy-bundle

---


🌸 Usage – Bloom Platform
👤 Secure & Intelligent Access
AI-enhanced authentication with biometric verification, 2FA, and role-based access control for patients, health professionals, and administrators.

🧠 AI-Powered Mental Health Agents
Leverage specialized AI agents for mental health evaluation, triage, and personalized recommendations. Agents assist professionals in identifying risks and suggesting follow-up actions based on patient interactions.

🧑‍⚕️ Patient-Centered Care Management
Build and maintain detailed patient profiles, including mental health history, therapy sessions, and treatment plans — all accessible by authorized professionals.

📈 Behavioral & Emotional Analytics
Visualize trends in mental health using AI-driven analytics. Detect emotional shifts, engagement patterns, and risk indicators to inform proactive intervention.

💬 Conversational Follow-Up Agents
Smart agents guide patients through structured therapeutic conversations, daily check-ins, and cognitive-behavioral exercises tailored to their profiles.

📆 Integrated Appointment Scheduling
Schedule in-person or video consultations with health professionals. Includes:

AI-based pre-session screening

Follow-up reminders

Calendar integration for therapists and patients

📬 Smart Notifications
Timely alerts for upcoming sessions, self-care tasks, and medication routines—sent via email, app, or SMS.

📁 Secure Medical Record Handling
Manage and share electronic health records (EHRs) with strict data privacy controls. AI agents assist administrators in flagging anomalies and optimizing data workflows.

🔐 Admin Control & Governance
Administrators oversee platform integrity, manage user roles, and monitor compliance. AI tools help track system usage, ensure data protection, and support decision-making.



---


---

## Contributions

We sincerely thank everyone who contributed to this project!

### Contributors

The following people have contributed by adding key features, fixing bugs, and enhancing the user experience:

- **[Hedi Brahmi](https://github.com/hedibrahmi)**: Developed the user module, including profile management, advanced authentication, and personalization.
- **[Mehdi Bani](https://github.com/mahdibani)**: Developed the core platform architecture and AI integration framework.
- **[Hidaya Hanafi](https://github.com/hidayahanafi)**: Implemented the patient management system and electronic health records.
- **[Ahmed Bettaieb](https://github.com/ahmedbt20)**: Created intuitive UX/UI design and accessibility features.
- **[Ahmed Karray](https://github.com/ahmed-karray)**: Designed the responsive frontend interfaces and interactive dashboards.
- **[Syrine Wannes](https://github.com/syrine22c)**: Engineered the database architecture and secure data management systems.

---

💡 **Want to contribute? Here's how:**

1. Fork the project  
2. Create a branch `feature/your-feature`  
3. Make your changes  
4. Commit & push  
5. Open a Pull Request 🚀

---
---

## Acknowledgements

I would like to sincerely thank my professors [Mrs. Safouene Zouari](mailto:safouene.zouari@esprit.tn) and [Mr. MoetezHechmi GROUN](mailto:moetezhechmi.groun@esprit.tn) for their guidance, availability, and valuable advice throughout this project.

Thank you for your support, dedication, and encouragement!

This project was carried out as part of our training at **ESPRIT – Private Higher School of Engineering and Technology**.



## License

Project under the **MIT** license.

```
MIT License

Copyright (c) 2025

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction...

```

---

> 🏁 This project was developed as part of our training at **ESPRIT**.
