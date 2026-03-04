import faiss
import json
import pandas as pd
import numpy as np
import sys
import pickle

def export_all():
    print("Exporting Excel...")
    try:
        df_excel = pd.read_excel('DTP_Database.xlsx')
        df_excel.to_json('dtp_database.json', orient='records')
        print("Excel copied to dtp_database.json")
    except Exception as e:
        print("Excel error:", e)

    print("Exporting Pickle...")
    try:
        data = pd.read_pickle('pon_data.pkl')
        if isinstance(data, pd.DataFrame):
            data.to_json('pon_data.json', orient='records')
        elif isinstance(data, list) or isinstance(data, dict):
            # sometimes contains numpy arrays or other non-serializable objects
            # let's try a simple conversion or stringification if needed
            # for now just try to dump
            # but if it has numpy arrays, we need a custom encoder
            class NpEncoder(json.JSONEncoder):
                def default(self, obj):
                    if isinstance(obj, np.integer):
                        return int(obj)
                    if isinstance(obj, np.floating):
                        return float(obj)
                    if isinstance(obj, np.ndarray):
                        return obj.tolist()
                    return super(NpEncoder, self).default(obj)
            with open('pon_data.json', 'w') as f:
                json.dump(data, f, cls=NpEncoder)
        else:
            print("Unknown pickle type:", type(data))
        print("Pickle copied to pon_data.json")
    except Exception as e:
        print("Pickle error:", e)

    print("Exporting FAISS...")
    try:
        index = faiss.read_index('pon_index.faiss')
        vectors = []
        for i in range(index.ntotal):
            vec = index.reconstruct(i)
            vectors.append(vec.tolist())
        with open('pon_index.json', 'w') as f:
            json.dump(vectors, f)
        print(f"FAISS copied to pon_index.json (Total vectors: {index.ntotal}, Dimension: {index.d})")
    except Exception as e:
        print("FAISS error:", e)

if __name__ == '__main__':
    export_all()
