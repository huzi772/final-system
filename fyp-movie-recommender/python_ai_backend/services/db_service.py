import mysql.connector
from mysql.connector import Error
import os

# Function to parse PHP config for database credentials
def get_db_config_from_php():
    """
    Attempts to read DB credentials from the PHP config file.
    """
    config_path = os.path.join(os.path.dirname(__file__), '..', '..', 'php_backend', 'includes', 'config.php')
    config = {
        'host': 'localhost',
        'database': 'mood_recommender_db',
        'user': 'root',
        'password': ''
    }

    if os.path.exists(config_path):
        try:
            with open(config_path, 'r') as f:
                content = f.read()
                import re
                # Simple regex to extract define('NAME', 'VALUE')
                host = re.search(r"define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*['\"](.*?)['\"]\s*\)", content)
                name = re.search(r"define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"](.*?)['\"]\s*\)", content)
                user = re.search(r"define\s*\(\s*['\"]DB_USER['\"]\s*,\s*['\"](.*?)['\"]\s*\)", content)
                pw = re.search(r"define\s*\(\s*['\"]DB_PASS['\"]\s*,\s*['\"](.*?)['\"]\s*\)", content)

                if host: config['host'] = host.group(1)
                if name: config['database'] = name.group(1)
                if user: config['user'] = user.group(1)
                if pw: config['password'] = pw.group(1)
        except Exception as e:
            print(f"Warning: Could not parse PHP config: {e}")

    return config

DB_CONFIG = get_db_config_from_php()

def get_db_connection():
    """
    Creates and returns a connection to the shared MySQL database.
    """
    try:
        connection = mysql.connector.connect(
            host=DB_CONFIG['host'],
            database=DB_CONFIG['database'],
            user=DB_CONFIG['user'],
            password=DB_CONFIG['password']
        )
        if connection.is_connected():
            return connection
    except Error as e:
        # Avoid flooding logs if DB is not available
        return None

def query_mapping_from_db(mood_name):
    """
    Queries the mood_genre_mapping table for the highest weighted genre.
    """
    connection = get_db_connection()
    if not connection:
        return None

    try:
        cursor = connection.cursor(dictionary=True)
        query = "SELECT genre_id FROM mood_genre_mapping WHERE mood_name = %s ORDER BY weight DESC LIMIT 1"
        cursor.execute(query, (mood_name.capitalize(),))
        result = cursor.fetchone()
        return result['genre_id'] if result else None
    except Error as e:
        print(f"Error querying database: {e}")
        return None
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

def query_all_mappings_from_db():
    """
    Queries all mood-to-genre mappings from the database.
    """
    connection = get_db_connection()
    if not connection:
        return None

    try:
        cursor = connection.cursor(dictionary=True)
        query = "SELECT mood_name, genre_id FROM mood_genre_mapping ORDER BY weight DESC"
        cursor.execute(query)
        results = cursor.fetchall()

        # Build a dictionary, later entries for the same mood will be ignored due to DESC weight
        mapping = {}
        for row in results:
            if row['mood_name'] not in mapping:
                mapping[row['mood_name']] = row['genre_id']
        return mapping
    except Error as e:
        print(f"Error querying all mappings from database: {e}")
        return None
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()
