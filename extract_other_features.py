#!/usr/bin/env python3
import re

def extract_between_sections():
    menu_file = 'resources/views/partials/admin/menu.blade.php.original'
    
    # Try to read the original file if it exists
    try:
        with open(menu_file, 'r', encoding='utf-8') as f:
            content = f.read()
    except FileNotFoundError:
        # Try the current menu file (but it's already modified)
        menu_file = 'resources/views/partials/admin/menu.blade.php'
        with open(menu_file, 'r', encoding='utf-8') as f:
            content = f.read()
    
    # Find "End POs System" and "Start System Setup"
    # Note: The section name might be "POs System" or "POS System"
    end_pos_pattern = r'<!-+\s*End\s+POs System\s*-+>'
    start_system_pattern = r'<!-+\s*Start\s+System Setup\s*-+>'
    
    end_match = re.search(end_pos_pattern, content, re.IGNORECASE)
    start_match = re.search(start_system_pattern, content, re.IGNORECASE)
    
    if end_match and start_match:
        start_pos = end_match.end()
        end_pos = start_match.start()
        
        other_content = content[start_pos:end_pos].strip()
        
        if other_content:
            print("Found content between 'End POs System' and 'Start System Setup':")
            print("=" * 80)
            print(other_content[:500] + "..." if len(other_content) > 500 else other_content)
            print("=" * 80)
            
            # Save to file
            output_path = 'resources/views/partials/admin/menu/other-features.blade.php'
            with open(output_path, 'w', encoding='utf-8') as f:
                f.write(other_content)
            
            print(f"\nSaved to {output_path}")
            return True
        else:
            print("No content found between sections")
            return False
    else:
        print("Could not find the section boundaries")
        if not end_match:
            print("Could not find 'End POs System'")
        if not start_match:
            print("Could not find 'Start System Setup'")
        return False

if __name__ == '__main__':
    extract_between_sections()
