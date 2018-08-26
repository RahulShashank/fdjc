

    var page = require('webpage').create();

    page.viewportSize = { width: 900, height: 350 };

    

    page.open('http://localhost/biteanalytics/common/aircraftTimeLineForReport.php?aircraftId=132&startDateTimeForScreenShot=2018-02-01&endDateTimeForScreenShot=2018-07-30', function () {
        page.render('QTR_A7_ADV.jpg');
        phantom.exit();
    });


    