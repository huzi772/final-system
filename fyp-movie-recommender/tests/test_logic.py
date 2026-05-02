import sys
import os

# Add services to path
sys.path.append(os.path.join(os.path.dirname(__file__), '..', 'python_ai_backend'))

from services.mood_to_genre import get_genre_for_mood

def test_mood_to_genre():
    print("Testing Mood to Genre Mapping...")

    # Test a few moods
    moods_to_test = ['Happy', 'Sad', 'Angry', 'UnknownMood']

    for mood in moods_to_test:
        try:
            genre_id = get_genre_for_mood(mood)
            print(f"Mood: {mood} -> Genre ID: {genre_id}")
        except Exception as e:
            print(f"Error testing mood '{mood}': {e}")

if __name__ == "__main__":
    test_mood_to_genre()
