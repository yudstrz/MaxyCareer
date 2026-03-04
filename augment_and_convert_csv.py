import pandas as pd
import json
import random
import os

def augment_data(df, target_rows=200):
    current_rows = len(df)
    if current_rows >= target_rows:
        return df.head(target_rows)
    
    additional_rows_needed = target_rows - current_rows
    new_rows = []
    
    # Extract unique values to mix and match
    areas = df['Area_Fungsi'].unique().tolist()
    
    for i in range(additional_rows_needed):
        # Pick a random existing row as a template
        source_row = df.iloc[random.randint(0, current_rows - 1)]
        
        # Determine Area_Fungsi
        area = source_row['Area_Fungsi']
        
        # Generate a new Okupasi name by slightly modifying an existing one
        original_okupasi = source_row['Okupasi']
        new_okupasi = f"Senior {original_okupasi}" if i % 2 == 0 else f"Junior {original_okupasi}"
        
        # Create a new unique ID
        new_id = f"PON-AUG-{i+1:03d}"
        
        new_rows.append({
            'OkupasiID': new_id,
            'Area_Fungsi': area,
            'Okupasi': new_okupasi,
            'Unit_Kompetensi': source_row['Unit_Kompetensi'],
            'Kuk_Keywords': source_row['Kuk_Keywords']
        })
    
    augmented_df = pd.concat([df, pd.DataFrame(new_rows)], ignore_index=True)
    return augmented_df

def main():
    csv_file = 'DTP.csv'
    output_file = 'public/datasets/DTP_Database.jsonl'
    
    print(f"Reading {csv_file}...")
    # The file uses semicolon as separator based on view_file output
    df = pd.read_csv(csv_file, sep=';')
    
    print(f"Original rows: {len(df)}")
    df_augmented = augment_data(df, target_rows=200)
    print(f"Augmented rows: {len(df_augmented)}")
    
    # Ensure directory exists
    os.makedirs(os.path.dirname(output_file), exist_ok=True)
    
    print(f"Saving to {output_file}...")
    with open(output_file, 'w', encoding='utf-8') as f:
        for record in df_augmented.to_dict('records'):
            f.write(json.dumps(record) + '\n')
            
    print("Done!")

if __name__ == "__main__":
    main()
