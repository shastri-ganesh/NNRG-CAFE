import re

with open('/Users/ganeshshastri/Developer/public_html/admin/admin_order_update.php', 'r') as file:
    content = file.read()

# Replace any $row[" whatever \n "] with $row['whatever']
content = re.sub(r'\$row\["\s*([^"\n\r]*)[\n\r]*\s*([^"]*)"\]', lambda m: f"$row['{m.group(1).strip()}{m.group(2).strip()}']", content)
content = re.sub(r'\$row\["\s*([^"]+)"\]', lambda m: f"$row['{m.group(1).strip()}']", content)
content = re.sub(r'\$row\[\s*"([^"]+)"\s*\]', lambda m: f"$row['{m.group(1).strip()}']", content)

with open('/Users/ganeshshastri/Developer/public_html/admin/admin_order_update.php', 'w') as file:
    file.write(content)
