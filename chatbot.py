import google.generativeai as genai
import mysql.connector
from flask import Flask, request, jsonify
from flask_cors import CORS


genai.configure(api_key="AIzaSyAsJhhjw7mf-og50Tqnzn35COSHkGJKUWs")  # Remplace par ta vraie clé API


app = Flask(__name__)
CORS(app)  # Permettre les requêtes depuis Symfony

DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",  # Laissez vide si vous n'avez pas défini de mot de passe
    "database": "pidev"  # Remplacez par le vrai nom de votre base de données
}


def get_db_connection():
    """Créer une connexion à la base de données MySQL."""
    connection = mysql.connector.connect(**DB_CONFIG)
    connection.database = DB_CONFIG["database"]  # 🔹 Assurez-vous que la base est sélectionnée
    return connection

# 🔹 Fonction pour récupérer la liste des pharmacies
def get_pharmacies():
    try:
        connection = get_db_connection()
        cursor = connection.cursor(dictionary=True)
        cursor.execute("SELECT nom, adresse, email, tel, type, ville FROM pharmacie")
        pharmacies = cursor.fetchall()
        cursor.close()
        connection.close()

        if not pharmacies:
            return "Aucune pharmacie trouvée."

        response = "**Liste des pharmacies :**\n"
        for p in pharmacies:
            response += f"- **{p['nom']}** 📍{p['adresse']}, 📧 {p['email']}, 📞 {p['tel']}, 🏥 {p['type']} ({p['ville']})\n"
        
        return response
    except Exception as e:
        return f"❌ Erreur lors de la récupération des pharmacies : {str(e)}"

# 🔹 Fonction pour récupérer la liste des médicaments
def get_medicaments():
    try:
        connection = get_db_connection()
        cursor = connection.cursor(dictionary=True)
        cursor.execute("SELECT nom, description, dosage, categorie, quantite, prix FROM medicament")
        medicaments = cursor.fetchall()
        cursor.close()
        connection.close()

        if not medicaments:
            return "Aucun médicament trouvé."

        response = "**Liste des médicaments :**\n"
        for m in medicaments:
            response += f"- **{m['nom']}** ({m['description']}), Dosage: {m['dosage']}mg, Catégorie: {m['categorie']}, Stock: {m['quantite']} unités, Prix: {m['prix']}€\n"
        
        return response
    except Exception as e:
        return f"❌ Erreur lors de la récupération des médicaments : {str(e)}"

# 🔹 Fonction pour interagir avec Google Gemini et la base de données
def get_response(user_input):
    user_input = user_input.lower()

    # Vérifier si l'utilisateur demande la liste des pharmacies ou médicaments
    if "liste des pharmacies" in user_input or "pharmacies disponibles" in user_input:
        return get_pharmacies()
    elif "liste des médicaments" in user_input or "médicaments disponibles" in user_input:
        return get_medicaments()
    
    # Si ce n'est pas une demande spéciale, utiliser Google Gemini
    try:
        model = genai.GenerativeModel("gemini-1.5-pro-latest")
        response = model.generate_content(user_input)
        return response.text if response else "Désolé, je n'ai pas compris votre demande."
    except Exception as e:
        return f"❌ Erreur AI : {str(e)}"

@app.route("/chatbot", methods=["POST"])
def chatbot():
    try:
        user_input = request.json.get("message", "").strip()
        if not user_input:
            return jsonify({"response": "⚠️ Veuillez entrer un message valide."})

        # Obtenir la réponse de l'IA ou de la base de données
        response_text = get_response(user_input)

        return jsonify({"response": response_text})
    except Exception as e:
        return jsonify({"response": f"❌ Erreur serveur : {str(e)}"}), 500

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)