

    var page = require('webpage').create();

    page.viewportSize = { width: 900, height: 350 };

    

    page.open('http://localhost/biteanalytics/common/aircraftTimeLineForReport.php?aircraftId=1326&startDateTimeForScreenShot=2018-07-18&endDateTimeForScreenShot=2018-07-24', function () {
        page.render('QTR_A7_ANA.jpg');
        phantom.exit();
    });


    