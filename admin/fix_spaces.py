import sys

with open('admin_shop_list.php', 'r') as f:
    text = f.read()

# Replace all occurrences of `$row[" s_id"]` with `$row["s_id"]`
new_text = text.replace('$row[" s_id"]', '$row["s_id"]')

with open('admin_shop_list.php', 'w') as f:
    f.write(new_text)

print("Replaced successfully!")
