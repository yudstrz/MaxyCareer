import json
import numpy as np

try:
    from sentence_transformers import SentenceTransformer
except ImportError:
    print("Please install sentence-transformers: pip install sentence-transformers")
    exit(1)

def main():
    print("Loading AI Model (all-MiniLM-L6-v2)...")
    model = SentenceTransformer('all-MiniLM-L6-v2')
    
    # 1. Load the scraped jobs
    try:
        with open('public/datasets/dtp_database.json', 'r', encoding='utf-8') as f:
            jobs = json.load(f)
    except Exception as e:
        print("Error loading dtp_database.json:", e)
        return

    print(f"Loaded {len(jobs)} jobs. Generating embeddings...")
    
    # 2. Extract texts to embed (Title + Company + Location)
    texts_to_embed = []
    for job in jobs:
        # We create a descriptive string
        text = f"{job.get('title', '')} at {job.get('company', '')} located in {job.get('location', '')}. {job.get('description', '')}"
        texts_to_embed.append(text)

    # 3. Generate embeddings
    embeddings = model.encode(texts_to_embed, show_progress_bar=True)
    
    # 4. Save to pon_index.json (same format our JS expects)
    # Convert numpy arrays to lists
    vectors_list = embeddings.tolist()
    
    with open('public/datasets/pon_index.json', 'w', encoding='utf-8') as f:
        json.dump(vectors_list, f)

    print(f"Successfully saved {len(vectors_list)} embeddings to public/datasets/pon_index.json")

if __name__ == '__main__':
    main()
