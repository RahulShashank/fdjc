

    var page = require('webpage').create();

    page.viewportSize = { width: 900, height: 350 };

    

    page.open('http://localhost/biteanalytics/common/aircraftTimeLineForReport.php?$param&startDateTimeForScreenShot=2016-11-08&endDateTimeForScreenShot=2018-07-23', function () {
        page.render('QTR_A7_ADS.jpg');
        phantom.exit();
    });


    