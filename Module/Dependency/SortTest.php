<?php

declare(strict_types=1);

/*
 * 本文件由 秋枫雁飞 编写，所有解释权归Aiweline所有。
 * 邮箱：aiweline@qq.com
 * 网址：aiweline.com
 * 论坛：https://bbs.aiweline.com
 */

namespace Weline\Framework\Module\Dependency;

use Weline\Framework\UnitTest\TestCore;

class SortTest extends TestCore
{
    public function testDependenciesSort()
    {
        /**@var Sort $sort*/
        $sort = $this->getInstance(Sort::class);
        $ds = [
            ['id' => 'top', 'parent' => ['docBody']],
            ['id' => 'header_border_container', 'parent' => ['top']],
            ['id' => 'header_left', 'parent' => ['header_border_container']],
            ['id' => 'header_center', 'parent' => ['header_border_container']],
            ['id' => 'system_toolbar', 'parent' => ['header_center']],
            ['id' => 'Notifications_Button', 'parent' => ['Notification_Dialog', 'system_toolbar']],
            ['id' => 'User_Menu_Button', 'parent' => ['system_toolbar', 'User_Menu']],
            ['id' => 'User_Menu', 'parent' => ['header_center']],
            ['id' => 'User_Menu_LogOut', 'parent' => ['User_Menu']],
            ['id' => 'User_Menu_Change_Password', 'parent' => ['User_Menu', 'User_Menu_LogOut']],
            ['id' => 'Notifications_Store', 'parent' => ['header_center']],
            ['id' => 'left', 'parent' => ['docBody']],
            ['id' => 'menu_accordian', 'parent' => ['left']],
            ['id' => 'ScreenContainer', 'parent' => ['docBody']],
            ['id' => 'InfoDialog', 'parent' => ['docBody']],
            ['id' => 'ID_BC', 'parent' => ['InfoDialog']],
            ['id' => 'InfoDialogContent', 'parent' => ['ID_BC']],
            ['id' => 'change_password_dialog', 'parent' => ['docBody']],
            ['id' => 'toaster', 'parent' => []],
            ['id' => 'Notification_Dialog', 'parent' => []],
            ['id' => 'Notifications_Grid', 'parent' => ['Notification_Dialog', 'Notifications_Store']],
            ['id' => 'docBody', 'parent' => []],
        ];
        p($sort->dependenciesSort($ds));
    }
}
