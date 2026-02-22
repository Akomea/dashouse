<?php
/**
 * Standardized Admin Navigation Component
 * Ensures consistent navigation across all admin pages
 */

function getAdminNavigation($currentPage = '') {
    $navItems = [
        'dashboard.php' => ['icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard'],
        'menu-manager.php' => ['icon' => 'fas fa-utensils', 'label' => 'Menu Manager'],
        'category-manager.php' => ['icon' => 'fas fa-tags', 'label' => 'Category Manager'],
        'photo-manager.php' => ['icon' => 'fas fa-images', 'label' => 'Photo Manager'],
        'gift-shop-manager.php' => ['icon' => 'fas fa-gifts', 'label' => 'Gift Shop Manager'],
        'business-info-manager.php' => ['icon' => 'fas fa-building', 'label' => 'Business Info'],
        'settings.php' => ['icon' => 'fas fa-cog', 'label' => 'Settings']
    ];
    
    $html = '<nav class="nav flex-column">';
    
    foreach ($navItems as $page => $item) {
        $isActive = ($currentPage === $page) ? 'active' : '';
        $html .= sprintf(
            '<a class="nav-link %s" href="%s"><i class="%s me-2"></i>%s</a>',
            $isActive,
            $page,
            $item['icon'],
            $item['label']
        );
    }
    
    $html .= '</nav>';
    
    return $html;
}

function renderAdminSidebar($currentPage = '') {
    return '
    <div class="sidebar p-3">
        <div class="text-center mb-4">
            <i class="fas fa-cat fa-2x mb-2"></i>
            <h5>Das House</h5>
            <small class="text-white-50">Admin Panel</small>
        </div>
        
        ' . getAdminNavigation($currentPage) . '
        
        <div class="mt-auto pt-5">
            <a href="dashboard.php?logout=1" class="btn btn-danger w-100">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>
    </div>';
}
?>
