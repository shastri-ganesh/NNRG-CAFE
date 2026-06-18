<footer class="footer bg-secondary text-light py-3 px-3 mt-auto">
    <div class="container-fluid">
        <div class="row align-items-center">
            <!-- Copyright text -->
            <div class="col-10 col-sm-11">
                <span class="d-block" style="font-size: 0.9rem;">&copy; The Alpha Achievers</span>
                <span class="d-block" style="font-size: 0.75rem; opacity: 0.9;">Designed and developed by Ganesh Shastri and Vamshi (Information Technology)</span>
            </div>
            
            <!-- Call button -->
            <div class="col-2 col-sm-1 text-end">
                <a class="text-light" target="_blank" href="footdirect.php" style="text-decoration: none;">
                    <div style="width: 40px; height: 40px; background-color: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-left: auto;">
                        <i class="bi bi-telephone" style="font-size: 18px;"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>
</footer>

<style>
/* Ensure the body and html take full height */
html, body {
    height: 100%;
}

/* Make sure your main container uses flexbox */
body {
    display: flex;
    flex-direction: column;
}

/* Main content should grow to fill available space */
main, .main-content, .container-fluid.flex-grow-1 {
    flex: 1;
}

/* Simple mobile adjustments */
@media (max-width: 576px) {
    .footer .col-10 span:first-child {
        font-size: 0.85rem !important;
    }
    
    .footer .col-10 span:last-child {
        font-size: 0.7rem !important;
    }
    
    .footer .col-2 div {
        width: 35px !important;
        height: 35px !important;
    }
    
    .footer .col-2 i {
        font-size: 16px !important;
    }
}
</style>