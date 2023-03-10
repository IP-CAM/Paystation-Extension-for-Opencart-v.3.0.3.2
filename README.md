# Paystation payment module for Opencart

This integration is currently only tested up to Opencart 3.0.3.8

## Requirements
* An account with [Paystation](https://www2.paystation.co.nz/)
* An HMAC key for your Paystation account, contact our support team if you do not already have this <support@paystation.co.nz>

## Installation

These instructions will guide you through installing the module and conducting a test transaction.

1. Download this plugin and rename the downloaded folder to `paystation.ocmod.zip`

2. Login to the admin panel of your Opencart website

3. Navigate to `Extensions -> Installer`

4. Click on the upload button and upload this plugins zip folder

5. Wait until the progress bar changes color to green

6. You can now find our module under `Extensions -> Payments`

7. Find Paystation in the list of payment methods, and click the green `Install` button in the `Action` column.

8. The page will reload. This time click the blue `Edit` button in the Action column for the Paystation method.

9. In the Paystation ID field, put your Paystation ID provided by Paystation.

10. In the Paystation Gateway field, put the Gateway ID provided by Paystation.

11. In the HMAC field, put the HMAC key provided by Paystation.

12. `Title in checkout` is the text that will display next to the payment method in the checkout.

13. We strongly suggest setting `Enable Postback` to `Yes` as it will allow the cart to capture payment results even if your customers re-direct is interrupted. However, if your development/test environment is local or on a network that cannot receive connections from the internet, you must set `Enable Postback` to `No`.

Your Paystation account needs to reflect your Opencart settings accurately, otherwise order status will not update correctly. Email support@paystation.co.nz with your Paystation ID and advise whether `Enable Postback` is set to `Yes` or `No` in your Opencart settings.

14. In the Order Status, you may choose a status for orders with successful payments.

15. Change the Status to Enabled.

16. Optionally, you may enter a number in the sort order field to change the order of appearance of payment methods in the checkout.

17. Optionally, select a Geo Zone. Refer to the Opencart documentation for details.

18. Click the blue `Save` button at the top-right of the page. 

19. The message `Success: You have modified Paystation three-party module details!` will appear.

20. Go to your online store. 

21. To do a successful test transaction, make a purchase where the final 
cost will have the cent value set to .00, for example $1.00, this will 
return a successful test transaction. To do an unsuccessful test transaction 
make a purchase where the final cost will have the cent value set to 
anything other than .00, for example $1.01-$1.99, this will return an 
unsuccessful test transaction. 

22. Important: You can only use the test Visa and Mastercards supplied by 
Paystation for test transactions. They can be found here https://www2.paystation.co.nz/developers/test-cards/.

23. When you go to checkout - make sure you choose Paystation Payment Gateway in the Payment method section. 

24. If everything works ok, go back to the `Payment Methods` page, find the Paystation module, and click the Configure link. 

25. Change the mode from `Test` to `Live` and click the Update button 

26. Fill in the form found on https://www2.paystation.co.nz/golive so that Paystation can test and set your account into Production Mode. 

26. Congratulations - you can now process online credit card payments
