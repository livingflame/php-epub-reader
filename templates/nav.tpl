<a class="toc" href="#">TOC</a>
<ul class="pagination">
<?php if ($show_page > 1) { ?>
    <li class="first"><a href="<?php echo $nav_address; ?>&show=1">&laquo;</a></li>
    <li><a href="<?php echo $nav_address; ?>&show=<?php echo ($show_page-1); ?>" title="prev_page">&lt;</a></li>
<?php } else { ?>
    <li class="first"><span>&laquo;</span></li>
    <li><span>&lt;</span></li>
<?php }
if ($show_page < $chapter_count) {  ?>
    <li><a href="<?php echo $nav_address; ?>&show=<?php echo ($show_page+1); ?>" title="next_page">&gt;</a></li>
    <li class="last"><a href="<?php echo $nav_address; ?>&show=<?php echo $chapter_count; ?>">&raquo;</a></li>
<?php } else {  ?>
    <li><span>&gt;</span></li>
    <li class="last"><span>&raquo;</span></li>
<?php }  ?>
</ul>