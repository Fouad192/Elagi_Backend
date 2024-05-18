import pytesseract
from PIL import Image
import re
import pandas as pd
import json
import sys

def extract_calcium_value(image_path):
    image = Image.open(image_path)
    text = pytesseract.image_to_string(image)
    calcium_regex = r"Calcium[\s\S]*?([\d.]+)\s*mg/dL"
    matches = re.findall(calcium_regex, text, re.IGNORECASE | re.DOTALL)
    for match in matches:
        if re.match(r'^\d+\.\d+$', match):
            return float(match)
    return None

def compare_with_dataset(extracted_value, dataset, result_mapping):
    if extracted_value is None:
        return {
            "condition": "Not available",
            "status": "Calcium value not found in the image",
            "reference_range": "Not available",
            "result_code": None,
            "extracted_value": None,
            "nearest_condition": "Not available",
            "nearest_value": None
        }

    ref_range = dataset['Calcium, Total Reference Range'].iloc[0]
    lower_bound, upper_bound = map(float, ref_range.split('-'))

    # Calculating the minimum absolute difference to find the nearest value in the 'Total Calcium' column
    dataset['Difference'] = dataset['Calcium, Total'].apply(lambda x: abs(x - extracted_value))
    nearest_row = dataset.loc[dataset['Difference'].idxmin()]

    if extracted_value < lower_bound:
        condition = "Hypocalcemia"
        status = "Below Normal"
    elif extracted_value > upper_bound:
        condition = "Hypercalcemia"
        status = "Above Normal"
    else:
        condition = "Normal"
        status = "Within Normal Range"

    result_code = result_mapping[condition]

    # Identify the condition associated with the nearest result value
    nearest_condition = None
    for key, value in result_mapping.items():
        if value == nearest_row['Result'].item():
            nearest_condition = key
            break

    return {
        "condition": condition,
        "status": status,
        "reference_range": ref_range,
        "result_code": result_code,
        "extracted_value": extracted_value,
        "difference": nearest_row['Difference'].item(),
        "nearest_result_value": nearest_row['Result'].item(),
        "nearest_condition": nearest_condition or "Condition not mapped"
    }

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python script.py <image_path>")
        sys.exit(1)

    image_path = sys.argv[1]
    dataset_path = "E:\\Graduation Project\\Pharmacy-Back-End\\public\\scripts\\Calcium.xlsx"

    # Read the dataset
    dataset = pd.read_excel(dataset_path)

    # Result column condition mapping
    Calcium_Result_Column_Mapping = {
        'hypoalbuminemia': 1,
        'Hypocalcemia': 2,
        'Hypercalcemia': 3,
        'hyperparathyroidism': 4,
        'hyperproteinemia': 5,
        'hypoparathyroidism': 6,
        'Normal': 0,
    }

    # Extract the calcium value from the image
    extracted_value = extract_calcium_value(image_path)

    # Compare with dataset and find the nearest value and condition
    results = compare_with_dataset(extracted_value, dataset, Calcium_Result_Column_Mapping)

    # Print the results as JSON
    json_results = json.dumps(results, indent=4)
    print(json_results)
