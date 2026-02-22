export function SiteFooter() {
  return (
    <footer
      id="footer"
      className="dark noborder pt-3"
      style={{
        backgroundImage: "url('/demos/burger/images/others/footer.jpg')",
      }}
      data-top-bottom="background-position:100% -300px"
      data-bottom-top="background-position: 100% 300px"
    >
      <div id="copyrights">
        <div className="container">
          <div className="row col-mb-30">
            <div className="col-md-6 text-center text-md-start">
              Copyrights &copy; 2026 All Rights Reserved by Dashouse Inc.
              <br />
            </div>
            <div className="col-md-6 text-center text-md-end">
              <div className="d-flex justify-content-center justify-content-md-end">
                <a
                  href="https://www.instagram.com/dashousevienna"
                  className="social-icon si-small si-colored nobottommargin si-instagram"
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label="Instagram"
                >
                  <i className="icon-instagram" />
                  <i className="icon-instagram" />
                </a>
                <a
                  href="https://www.tiktok.com/@dashousevienna"
                  className="social-icon si-small si-colored nobottommargin si-tiktok"
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label="TikTok"
                >
                  <i className="icon-tiktok" />
                  <i className="icon-tiktok" />
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
