INSERT INTO customer_points
(c_id, points_earned, points_used, points_balance, earned_date, status, remark)
SELECT c_id, 20, 0, 20, NOW(), 'active', 'Festival Offer'
FROM customers;

-- to check the how much points added till now on which particular offer..!
--SELECT * 
--FROM customer_points
--WHERE remark='Welcome Offer';
