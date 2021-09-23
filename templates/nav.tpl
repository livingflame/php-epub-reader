<a class="library" href="<?php echo $base_url; ?>"><i class="icofont-library"></i> Library</a>
<a class="toc" href="#">TOC</a>
<ul class="pagination">
<?php if ($show_page > 1) { ?>
    <li class="first"><a href="<?php echo $nav_address; ?>&show=1"><i class="icofont-rounded-double-left"></i></a></li>
    <li><a href="<?php echo $nav_address; ?>&show=<?php echo ($show_page-1); ?>" title="prev_page"><i class="icofont-rounded-left"></i></a></li>
<?php } else { ?>
    <li class="first"><span><i class="icofont-rounded-double-left"></i></span></li>
    <li><span><i class="icofont-rounded-left"></i></span></li>
<?php }
if ($show_page < $chapter_count) {  ?>
    <li><a href="<?php echo $nav_address; ?>&show=<?php echo ($show_page+1); ?>" title="next_page"><i class="icofont-rounded-right"></i></a></li>
    <li class="last"><a href="<?php echo $nav_address; ?>&show=<?php echo $chapter_count; ?>"><i class="icofont-rounded-double-right"></i></a></li>
<?php } else {  ?>
    <li><span><i class="icofont-rounded-right"></i></span></li>
    <li class="last"><span><i class="icofont-rounded-double-right"></i></span></li>
<?php }  ?>
</ul>