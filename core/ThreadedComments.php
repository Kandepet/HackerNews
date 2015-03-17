<?php

namespace HackerNews;

class ThreadedComments {

   public $parents  = array();
   public $children = array();

   /**
    * @param array $comments
    */
   function __construct($comments) {
      foreach ($comments as $comment) {
         if ($comment['parent_id'] == 0) {
            $this->parents[$comment['comment_id']][] = $comment;
         } else {
            $this->children[$comment['parent_id']][] = $comment;
         }
      }
   }

   private function get_comment($comment, $depth) {
      $ago = Common::time_taken((time()-$comment['comment_time']));
      $comment['ago'] = $ago;

      return $comment;
   }

   private function get_parent($comment, $depth = 0) {
      $level = [];

      foreach ($comment as $c) {
         $comment = $this->get_comment($c, $depth);
         $level[$comment['comment_id']] = $comment;

         if (isset($this->children[$c['comment_id']])) {
            $level[$comment['comment_id']]['replies'] = $this->get_parent($this->children[$c['comment_id']], $depth + 1);
         }
      }
      return $level;
   }

   public function get_comments() {
      $comments = null;
      foreach ($this->parents as $c) {
         if ($comments == null) {
            $comments = $this->get_parent($c);
         } else {
            $comments = $comments + $this->get_parent($c);
         }
      }

      return $comments;
   }

}

?>
