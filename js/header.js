const headerId = "site-header";
const logoId = "site-header-logo";

jQuery(() => {
  headerMain();
});

function headerMain() {
  const header = $("#" + headerId);
  const logo = $("#" + logoId);
  let currentScrollPos = 0;
  let prevScrollPos = 0;

  $(window).scroll(() => {
    currentScrollPos = document.body.scrollTop || document.documentElement.scrollTop;

    // Is moving downward and past 80px and isn't already collapsed
    if ((currentScrollPos - prevScrollPos) > 5 && currentScrollPos > 80 && !header.hasClass("site-header-scroll")) {
      header.addClass("site-header-scroll");
      collapseSiteLogo(logo);
    // Is moving upward and past 80px and isn't already expanded
    } else if ((currentScrollPos - prevScrollPos) < -5 && currentScrollPos < 80 && header.hasClass("site-header-scroll")) {
      header.removeClass("site-header-scroll");
      $(document.body).animate({ scrollTop: 0 }, "slow");
      expandSiteLogo(logo);
    }

    prevScrollPos = currentScrollPos;
  });

}

function collapseSiteLogo(logo) {
  const smallLogoPath = logo.attr("src").replace("oregonflora-logo.png", "oregonflora-logo-sm.png");
  logo.stop().animate({ opacity: 0 }, 100, () => {
    logo.attr("src", smallLogoPath).animate({ opacity: 100 }, 100);
  });
}

function expandSiteLogo(logo) {
  const lgLogoPath = logo.attr("src").replace("oregonflora-logo-sm.png", "oregonflora-logo.png");
  logo.stop().animate({ opacity: 0 }, 100, () => {
    logo.attr("src", lgLogoPath).animate({ opacity: 100 }, 100);
  });
}
