#!/usr/bin/env python3
import os
import re

def extract_section(file_path, section_name):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # More flexible pattern to handle variations in comment format
    # Match Start section with any characters (including typos) and any number of dashes
    start_pattern = r'<!-+\s*Start\s+' + re.escape(section_name) + r'\s*-+>'
    # Match End section with any characters and any number of dashes
    end_pattern = r'<!-+\s*End\s+' + re.escape(section_name) + r'\s*-+>'
    
    start_match = re.search(start_pattern, content, re.IGNORECASE)
    if not start_match:
        print(f"Start pattern for '{section_name}' not found")
        return None
    
    # Find the end after the start
    content_after_start = content[start_match.end():]
    end_match = re.search(end_pattern, content_after_start, re.IGNORECASE)
    
    if not end_match:
        print(f"End pattern for '{section_name}' not found")
        return None
    
    section_content = content_after_start[:end_match.start()]
    return section_content.strip()

def main():
    menu_file = 'resources/views/partials/admin/menu.blade.php'
    
    # Try to extract User Management section (with typo)
    section_content = extract_section(menu_file, 'User Managaement System')
    
    if section_content:
        output_dir = 'resources/views/partials/admin/menu'
        os.makedirs(output_dir, exist_ok=True)
        
        filename = 'user-management.blade.php'
        filepath = os.path.join(output_dir, filename)
        
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(section_content)
        
        print(f"Extracted 'User Management System' to {filename}")
    else:
        print("Failed to extract User Management section")
        
        # Try alternative names
        for alt_name in ['User Management System', 'User Management', 'User Managaement']:
            section_content = extract_section(menu_file, alt_name)
            if section_content:
                filename = 'user-management.blade.php'
                filepath = os.path.join(output_dir, filename)
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(section_content)
                print(f"Extracted '{alt_name}' to {filename}")
                break

if __name__ == '__main__':
    main()
