#!/usr/bin/env python3
import argparse
import json
import sys
import mysql.connector
import face_recognition
import numpy as np

# --- Database Configuration --- #
# Update these with your database credentials.
DB_HOST = 'localhost'
DB_USER = 'root'
DB_PASSWORD = ''
DB_NAME = 'PiDev'

def get_db_connection():
    try:
        connection = mysql.connector.connect(
            host=DB_HOST,
            user=DB_USER,
            password=DB_PASSWORD,
            database=DB_NAME
        )
        return connection
    except mysql.connector.Error as err:
        print(json.dumps({"error": f"Database connection error: {err}"}))
        sys.exit(1)

def register_face(user_id, image_path):
    image = face_recognition.load_image_file(image_path)
    encodings = face_recognition.face_encodings(image)
    if not encodings:
        print(json.dumps({"error": "No face found in the image."}))
        sys.exit(1)
    face_encoding = encodings[0].tolist()

    conn = get_db_connection()
    cursor = conn.cursor()
    query = "INSERT INTO user_faces (user_id, face_encoding) VALUES (%s, %s)"
    encoding_json = json.dumps(face_encoding)
    try:
        cursor.execute(query, (user_id, encoding_json))
        conn.commit()
        result = {"success": True, "message": "Face registered successfully."}
    except mysql.connector.Error as err:
        result = {"error": f"Database error: {err}"}
    finally:
        cursor.close()
        conn.close()

    print(json.dumps(result))

def compare_face(image_path, threshold=0.6):
    # Load image and extract face encoding
    image = face_recognition.load_image_file(image_path)
    encodings = face_recognition.face_encodings(image)
    if not encodings:
        print(json.dumps({"error": "No face found in the image."}))
        sys.exit(1)
    input_encoding = encodings[0]

    # Fetch stored face encodings from database
    conn = get_db_connection()
    cursor = conn.cursor()
    query = "SELECT user_id, face_encoding FROM user_faces"
    cursor.execute(query)
    rows = cursor.fetchall()
    cursor.close()
    conn.close()

    matched_user = None
    min_distance = None
    # Compare input face with each stored encoding
    for row in rows:
        user_id, encoding_json = row
        stored_encoding = np.array(json.loads(encoding_json))
        distance = np.linalg.norm(stored_encoding - input_encoding)
        if min_distance is None or distance < min_distance:
            min_distance = distance
            matched_user = user_id

    if min_distance is not None and min_distance < threshold:
        result = {"success": True, "user_id": matched_user, "distance": min_distance}
    else:
        result = {"error": "No matching face found."}
    print(json.dumps(result))

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Face Recognition Script")
    parser.add_argument("mode", choices=["register", "compare"], help="Operation mode: register or compare")
    parser.add_argument("--user_id", type=int, help="User ID (required for registration)")
    parser.add_argument("--image", required=True, help="Path to the image file")
    parser.add_argument("--threshold", type=float, default=0.6, help="Distance threshold for matching")
    args = parser.parse_args()

    if args.mode == "register":
        if args.user_id is None:
            print(json.dumps({"error": "User ID is required for registration."}))
            sys.exit(1)
        register_face(args.user_id, args.image)
    elif args.mode == "compare":
        compare_face(args.image, args.threshold)
