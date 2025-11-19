<?php

class PluginUnreadnotificationsProfile extends Profile {
   
   static $rightname = "config";
   
   static function getAllRights() {
      return [
         ['rights' => [READ => __('Read')],
          'label'  => __('Unread Notifications'),
          'field'  => 'plugin_unreadnotifications'
         ]
      ];
   }
   
   static function addDefaultRights() {
      $profile = new self();
      foreach (self::getAllRights() as $right) {
         self::addRight(self::$rightname, 0, $right['rights'][READ], $right['field']);
      }
   }
   
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == 'Profile') {
         return __('Unread Notifications');
      }
      return '';
   }
   
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Profile') {
         $profile = new self();
         $profile->showForm($item->getID());
      }
      return true;
   }
   
   function showForm($profiles_id = 0) {
      echo '<div class="firstbloc">';
      if ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) {
         echo "<form method='post' action='".$this->getFormURL()."'>";
      }
      
      $profile = new Profile();
      $profile->getFromDB($profiles_id);
      
      $rights = $this->getAllRights();
      $profile->displayRightsChoiceMatrix($rights, [
         'canedit'       => $canedit,
         'default_class' => 'tab_bg_2',
         'title'         => __('General')
      ]);
      
      if ($canedit) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo "</div>";
   }
}