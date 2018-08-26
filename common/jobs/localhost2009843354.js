

    var page = require('webpage').create();

    page.viewportSize = { width: 900, height: 350 };

    

    page.open('http://localhost/biteanalytics/common/aircraftTimeLineForReport.php?aircraftId=1060&startDateTimeForScreenShot=2016-06-02&endDateTimeForScreenShot=2018-07-23', function () {
        page.render('FWI_F_HHAV.jpg');
        phantom.exit();
    });


    