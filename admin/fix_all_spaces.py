import os
import re

admin_dir = "/Users/ganeshshastri/Developer/public_html/admin"

for filename in os.listdir(admin_dir):
    if filename.endswith(".php"):
        filepath = os.path.join(admin_dir, filename)
        with open(filepath, "r", encoding="utf-8") as f:
            content = f.read()

        # Fix instances like $row[" f_name"] -> $row["f_name"]
        # or $row[' f_name'] -> $row['f_name']
        new_content = re.sub(r'\$row\["\s+', r'$row["', content)
        new_content = re.sub(r"\$row\['\s+", r"$row['", new_content)

        if new_content != content:
            with open(filepath, "w", encoding="utf-8") as f:
                f.write(new_content)
            print(f"Fixed {filename}")

print("Done.")
