🧠 HealthCare AI Platform  Intelligent Symfony Web Application (BLOOM)
Welcome to the HealthCare AI Platform, a modern web application built using Symfony. This project offers a seamless blend of mental health management, pharmaceutical information, and goal tracking, enhanced through the integration of advanced AI agents for intelligent content handling, decision-making, and emotion analysis.

📌 Project Overview
This platform supports multiple roles (patients, therapists, doctors) and includes modules for:

📝 Blog Management with embedded AI agents

💬 Comment Moderation via NLP

🏥 Pharmacy and Medication Lookup using a chatbot

🎯 Objective and Reward Tracking with guidance agents

😊 Emotion Detection for empathetic interaction

All powered by modular AI agents, scalable backend services, and a clean, service-oriented architecture.

⚙️ Tech Stack
Layer	Technology
Framework	Symfony 6.x
ORM	Doctrine ORM
Database	MySQL / MariaDB
Frontend	Twig, Bootstrap 5
AI Agents	FastAPI, LangChain, HuggingFace Transformers
Authentication	Symfony Security, JWT (optional OAuth2)
Containerization	Docker (optional)
Testing	PHPUnit, Postman

🧠 AI-Powered Modules
📝 Blog Intelligence
Integrated NLP agents handle content analysis and generation:

Agent	Functionality Description
SummaryAgent	Generates concise summaries of each blog post using extractive/abstractive summarization.
ImprovementAgent	Provides writing suggestions using LLMs (e.g., style, grammar, clarity).
TitleAgent	Suggests better SEO-optimized or catchy titles.
RelatedPostAgent	Recommends similar posts based on semantic similarity using embeddings (e.g., SBERT).
QualityAgent	Scores the post (0–100) using criteria like readability, engagement, and length.

All agents can be hosted using FastAPI and called asynchronously via internal REST APIs.

💬 Comment Moderation
A sentiment-aware moderation pipeline is built around:

Comment Scoring Agent: Computes a toxicity or sentiment score for each comment.

Threshold Rule: Any comment with a score < 0.5 is automatically blocked.

Model architecture: DistilBERT / RoBERTa fine-tuned for toxicity classification.

💊 Pharmacy and Medication Chatbot
Users can interact with a pharmaceutical assistant bot that provides:

Live medication availability

Pharmacy info (open status, contact, location)

Recommendations for alternatives

Bot implementation may include:

Named Entity Recognition (NER) for medicine/pharmacy names

RAG (Retrieval-Augmented Generation) for query answering

🎯 Goals and Rewards Module
Each user can track psychological or treatment objectives using:

Goal tracking with startDate, dueDate, status, and feedback.

ObjectiveHelperAgent: Answers questions about goals and tracks user progress.

RewardSystem: Linked via Recompense entity for motivation.

😌 Emotion Detection
This module processes user texts to detect emotions like:

Happiness 😄

Sadness 😢

Anger 😠

Anxiety 😰

Multiclass emotion classifier (e.g., BERT with emotion labels) is used to adapt UI messages and route users toward helpful resources.

🧱 Entity-Relationship Architecture
The platform follows a domain-driven design (DDD) with the following major entities:

plaintext
Copier
Modifier
User ─┬─< Planning ──< Appointment
      ├─< Post ──< Comment
      ├─< Objectif ──< Recompense
      └─< Event / Cour

Pharmacie ──< Medicament
Entities use Doctrine annotations for ORM mapping.

Relationships (1-N, N-M) are fully normalized.

Entities implement lifecycle callbacks (prePersist, preUpdate) for auditing.

🚀 Setup & Installation
bash
Copier
Modifier
# 1. Clone the repo
git clone https://github.com/your-org/healthcare-ai-platform.git
cd healthcare-ai-platform

# 2. Install dependencies
composer install

# 3. Configure .env
cp .env .env.local
# Edit DB, MAILER, etc.

# 4. Run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Start Symfony server
symfony server:start
Running AI Services (optional)
Navigate to the ai-agents/ directory

Each agent has its own FastAPI service with a requirements.txt and main.py

Example:

bash
Copier
Modifier
cd ai-agents/summary
uvicorn main:app --reload --port 8002
🧪 API Testing
Use Postman collection (postman/healthcare.postman_collection.json)

JWT or session-based auth supported

AI endpoints documented via Swagger (if FastAPI used)

📈 Future Enhancements
Multi-language support (i18n)

Real-time chat for patient-doctor interaction

Integration with wearables (Apple/Google Fit)

Notifications and reminders via email/SMS

👥 Contributing
Pull requests and issue reports are welcome! To contribute:

Fork the project

Create your feature branch (git checkout -b feature/my-feature)

Commit your changes

Push to the branch and open a PR

🧑‍💻 Authors
Bani Mehdi
Hei Brahmi
Hidaya Hanafi
Syrine Wannes
Ahmed Bettaieb
