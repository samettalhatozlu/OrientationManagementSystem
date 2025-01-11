import sys
import google.generativeai as genai

# UTF-8 çıktısı için stdout'u ayarla
sys.stdout.reconfigure(encoding='utf-8')

def get_gemini_response(user_prompt):
    try:
        genai.configure(api_key="AIzaSyBRxgIq46OkyP8Ef1uhQcJ39NxkSZZUL4Y")
        
        model = genai.GenerativeModel("gemini-1.5-flash")

        user_prompt_turkish = f"Lütfen Türkçe cevap ver: {user_prompt}"

        response = model.generate_content(user_prompt_turkish)

        return response.text
    except Exception as e:
        return f"API isteği sırasında hata oluştu: {str(e)}"

if __name__ == "__main__":
    user_prompt = sys.argv[1]  # PHP tarafından gönderilen prompt
    response = get_gemini_response(user_prompt)
    print(response)
