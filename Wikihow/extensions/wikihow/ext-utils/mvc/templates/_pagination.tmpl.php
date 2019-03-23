<? use \MVC\Paginator; ?>
<? if (Paginator::numPages() > 1): ?>
	<nav>
		<ul class="pagination pagination-sm">
			<? if (Paginator::currentPage() != 0): ?>
				<li>
					<a href="<?= paginationLink(Paginator::currentPage() - 1) ?>" aria-label="Previous">
						<span aria-hidden="true">&laquo;</span>
					</a>
				</li>
			<? endif; ?>
			<? 
				for ($i = 0; $i < Paginator::numPages(); $i ++) { 
					if ($i > Paginator::currentPage() - Paginator::$linkOffset && $i < Paginator::currentPage() + Paginator::$linkOffset):
				?>
				<li class="<?= $i == Paginator::currentPage() ? 'active' : '' ?>"><a href="<?= paginationLink($i) ?>"><?= $i + 1 ?></a></li>
			<? 
					endif;
				} 
			?>
			<? if ((Paginator::numPages() -1) != Paginator::currentPage()): ?>
				<li>
					<a href="<?= paginationLink(Paginator::currentPage() + 1) ?>" aria-label="Next">
						<span aria-hidden="true">&raquo;</span>
					</a>
				</li>
			<? endif; ?>
		</ul>
	</nav>
<? endif; ?>
