#!/usr/bin/env python3
import os

def create_final_menu():
    # List of partials in the order they should appear
    partials = [
        'dashboard.blade.php',
        'hrm.blade.php', 
        'account.blade.php',
        'crm.blade.php',
        'project.blade.php',
        'user-management.blade.php',
        'products-system.blade.php',
        'pos-system.blade.php',
        # 'other-features.blade.php',  # Will add later if needed
        'system-setup.blade.php'
    ]
    
    # Read the wrapper
    wrapper_path = 'resources/views/partials/admin/menu-wrapper.blade.php'
    with open(wrapper_path, 'r', encoding='utf-8') as f:
        wrapper_content = f.read()
    
    # Find the empty <ul class="dash-navbar"> for non-client users
    # We need to insert the partials inside this ul
    target = '<ul class="dash-navbar">\n                '
    
    if target in wrapper_content:
        # Build the includes string
        includes = ''
        for partial in partials:
            includes += f'                @include(\'partials.admin.menu.{partial.replace(".blade.php", "")}\')\n'
        
        # Replace the empty ul content with includes
        new_ul_content = f'<ul class="dash-navbar">\n{includes}                '
        wrapper_content = wrapper_content.replace(target, new_ul_content)
        
        # Write the final menu file
        final_path = 'resources/views/partials/admin/menu.blade.php'
        with open(final_path, 'w', encoding='utf-8') as f:
            f.write(wrapper_content)
        
        print(f"Created final menu file at {final_path}")
        print(f"Included {len(partials)} partials")
        
        # List the partials included
        for i, partial in enumerate(partials, 1):
            print(f"  {i}. {partial}")
    else:
        print("Error: Could not find target <ul class=\"dash-navbar\"> in wrapper")
        
        # Try alternative target
        target2 = '<ul class="dash-navbar">'
        if target2 in wrapper_content:
            # Get the full line
            lines = wrapper_content.split('\n')
            for i, line in enumerate(lines):
                if target2 in line and '</ul>' in lines[i+1]:  # Empty ul
                    # Build includes
                    includes = ''
                    for partial in partials:
                        includes += f'                @include(\'partials.admin.menu.{partial.replace(".blade.php", "")}\')\n'
                    
                    lines[i] = f'{line}\n{includes}'
                    wrapper_content = '\n'.join(lines)
                    
                    final_path = 'resources/views/partials/admin/menu.blade.php'
                    with open(final_path, 'w', encoding='utf-8') as f:
                        f.write(wrapper_content)
                    
                    print(f"Created final menu file at {final_path} (using alternative method)")
                    print(f"Included {len(partials)} partials")
                    break

if __name__ == '__main__':
    create_final_menu()
