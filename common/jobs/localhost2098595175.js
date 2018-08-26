

    var page = require('webpage').create();

    page.viewportSize = { width: 900, height: 350 };

    

    page.open('http://localhost/biteanalytics/common/aircraftTimeLineForReport.php?aircraftId=973&startDateTimeForScreenShot=2018-07-18&endDateTimeForScreenShot=2018-07-24', function () {
        page.render('SVA_HZ_AK40.jpg');
        phantom.exit();
    });


    