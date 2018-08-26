

    var page = require('webpage').create();

    page.viewportSize = { width: 900, height: 350 };

    

    page.open('http://localhost/biteanalytics/common/aircraftTimeLineForReport.php?aircraftId=1382&startDateTimeForScreenShot=2018-07-16&endDateTimeForScreenShot=2018-07-22', function () {
        page.render('QTR_A7_APJ.jpg');
        phantom.exit();
    });


    