<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\UserGuideService;

class UserGuideDrawer extends Component
{
    public bool $isOpen = false;
    public string $activeMenu = 'dashboard';
    public string $searchQuery = '';

    public function mount()
    {
        $this->activeMenu = UserGuideService::detectMenuFromUrl();
    }

    #[On('open-user-guide')]
    public function openGuide(?string $menu = null, ?string $url = null)
    {
        if ($menu && array_key_exists($menu, UserGuideService::getMenuRegistry())) {
            $this->activeMenu = $menu;
        } else {
            $this->activeMenu = UserGuideService::detectMenuFromUrl($url);
        }

        $this->isOpen = true;
    }

    public function toggle()
    {
        if ($this->isOpen) {
            $this->close();
        } else {
            $this->openGuide();
        }
    }

    public function close()
    {
        $this->isOpen = false;
    }

    public function selectMenu(string $key)
    {
        if (array_key_exists($key, UserGuideService::getMenuRegistry())) {
            $this->activeMenu = $key;
        }
    }

    public function render()
    {
        $registry = UserGuideService::getMenuRegistry();
        $guide = UserGuideService::getGuideContent($this->activeMenu);

        // Filter menus jika ada query pencarian
        $filteredRegistry = $registry;
        if (!empty(trim($this->searchQuery))) {
            $q = strtolower(trim($this->searchQuery));
            $filteredRegistry = array_filter($registry, function ($item) use ($q) {
                return str_contains(strtolower($item['title']), $q) ||
                       str_contains(strtolower($item['summary']), $q) ||
                       str_contains(strtolower($item['category']), $q);
            });
        }

        // Kelompokkan menu berdasarkan Kategori
        $groupedMenus = [];
        foreach ($filteredRegistry as $key => $meta) {
            $groupedMenus[$meta['category']][$key] = $meta;
        }

        return view('livewire.user-guide-drawer', [
            'guide' => $guide,
            'groupedMenus' => $groupedMenus,
            'allRegistry' => $registry,
        ]);
    }
}
