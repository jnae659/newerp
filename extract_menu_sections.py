#!/usr/bin/env python3
import os
import re

def extract_sections(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Pattern to find sections between Start and End comments
    # Looking for patterns like:
    # <!--------------------- Start Section Name ----------------------------------->
    # ... content ...
    # <!--------------------- End Section Name ----------------------------------->
    pattern = r'<!--------------------- Start (.*?) ----------------------------------->(.*?)<!--------------------- End \1 ----------------------------------->'
    
    sections = re.findall(pattern, content, re.DOTALL)
    
    # Create output directory
    output_dir = 'resources/views/partials/admin/menu'
    os.makedirs(output_dir, exist_ok=True)
    
    extracted_sections = []
    
    for section_name, section_content in sections:
        # Clean up section name for filename
        filename = section_name.lower().replace(' ', '-').replace('&', 'and') + '.blade.php'
        filepath = os.path.join(output_dir, filename)
        
        # Write section content to file
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(section_content.strip())
        
        extracted_sections.append((section_name, filename))
        print(f"Extracted '{section_name}' to {filename}")
    
    # Also extract the main wrapper (everything before first section and after last section)
    # Find first section start
    first_section_match = re.search(r'<!--------------------- Start .*? ----------------------------------->', content)
    if first_section_match:
        wrapper_start = content[:first_section_match.start()]
    else:
        wrapper_start = content
    
    # Find last section end
    last_section_match = list(re.finditer(r'<!--------------------- End .*? ----------------------------------->', content))
    if last_section_match:
        wrapper_end = content[last_section_match[-1].end():]
    else:
        wrapper_end = ''
    
    wrapper_content = wrapper_start + wrapper_end
    
    # Save wrapper (main menu file without sections)
    wrapper_path = 'resources/views/partials/admin/menu-wrapper.blade.php'
    with open(wrapper_path, 'w', encoding='utf-8') as f:
        f.write(wrapper_content.strip())
    
    print(f"\nExtracted wrapper to menu-wrapper.blade.php")
    
    return extracted_sections, wrapper_content

if __name__ == '__main__':
    menu_file = 'resources/views/partials/admin/menu.blade.php'
    if os.path.exists(menu_file):
        sections, wrapper = extract_sections(menu_file)
        print(f"\nTotal sections extracted: {len(sections)}")
    else:
        print(f"Error: File {menu_file} not found")
