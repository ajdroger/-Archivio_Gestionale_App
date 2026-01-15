import sys
import json
import urllib.request
import urllib.error

def chat_with_ollama(prompt, model="llama3"):
    url = "http://127.0.0.1:11434/api/generate"
    data = {
        "model": model,
        "prompt": f"You are an Advanced AI Coding Assistant for the MCAG Project. Focus: PHP 8.2, Slim 4, Security, SQL Injection Prevention.\n\nQuery: {prompt}",
        "stream": False
    }
    
    try:
        req = urllib.request.Request(url, data=json.dumps(data).encode('utf-8'), headers={'Content-Type': 'application/json'})
        with urllib.request.urlopen(req, timeout=10) as response:
            result = json.loads(response.read().decode('utf-8'))
            return result.get('response', 'Error: No response field in JSON.')
            
    except urllib.error.URLError as e:
        return f"CONNECTION ERROR: Could not reach Ollama at {url}. Ensure 'ollama serve' is running.\nDetails: {e}"

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python ai_bridge.py <prompt>")
        sys.exit(1)
        
    prompt = " ".join(sys.argv[1:])
    print(chat_with_ollama(prompt))
