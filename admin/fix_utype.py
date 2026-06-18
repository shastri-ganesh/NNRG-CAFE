import os, glob

dir_path = '/Users/ganeshshastri/Developer/public_html/admin/'
count = 0

for filepath in glob.glob(dir_path + '*.php'):
    with open(filepath, 'r') as f:
        content = f.read()
    
    original = content
    
    # Simple redirect
    content = content.replace('if($_SESSION["utype"]!="ADMIN"){', 'if($_SESSION["utype"]!="ADMIN" && $_SESSION["utype"]!="SUPERADMIN"){')
    
    # Shop Admin combined redirect
    content = content.replace("if ($_SESSION['utype'] != 'ADMIN' && $_SESSION['utype'] != 'SHOP_ADMIN') {", "if ($_SESSION['utype'] != 'ADMIN' && $_SESSION['utype'] != 'SHOP_ADMIN' && $_SESSION['utype'] != 'SUPERADMIN') {")
    
    # PHP Alternative Syntax UI visibility
    content = content.replace("if (!isset($_SESSION['utype']) || $_SESSION['utype'] === 'ADMIN'):", "if (!isset($_SESSION['utype']) || $_SESSION['utype'] === 'ADMIN' || $_SESSION['utype'] === 'SUPERADMIN'):")
    
    if content != original:
        with open(filepath, 'w') as f:
            f.write(content)
        count += 1
        print("Updated " + os.path.basename(filepath))

print("Total files updated: " + str(count))
