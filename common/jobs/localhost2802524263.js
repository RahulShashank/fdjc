

    var page = require('webpage').create();

    page.viewportSize = { width: 900, height: 350 };

    

    page.open('http://localhost/biteanalytics/common/aircraftTimeLineForReport.php?$param&startDateTimeForScreenShot=2015-06-15&endDateTimeForScreenShot=2018-07-23', function () {
        page.render('FWI_F_HHAV.jpg');
        phantom.exit();
    });


    