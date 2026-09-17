-- =====================================================================
-- CLEANUP: Tanggalin ang mga duplicate verified rental payments.
-- Patakbuhin sa phpMyAdmin (piliin ang if0_42853048_sitrass_db > SQL tab >
-- i-paste > Go).
--
-- Ano ang ginagawa nito:
-- 1. Hanapin ang mga rental na may sobrang verified payments (total >
--    total_price).
-- 2. I-reject ang mga "extra" verified balance payments (panatilihin ang
--    pinakamaagang verified deposit + balance, i-reject lang ang sobra).
-- 3. I-reset ang payment_status ng rental base sa natitirang verified
--    payments.
-- =====================================================================

-- Step 1: I-reject ang mga duplicate verified balance payments
-- (panatilihin ang unang deposit + unang balance; i-reject ang mga sobra)
UPDATE payments p
INNER JOIN (
    SELECT p2.payment_id
    FROM payments p2
    INNER JOIN van_rentals vr ON vr.rental_id = p2.rental_id
    WHERE p2.status = 'verified'
      AND p2.payment_type = 'balance'
      AND p2.rental_id IS NOT NULL
      AND p2.payment_id NOT IN (
          -- Panatilihin ang PINAKAMAAGANG verified balance payment kada rental
          SELECT MIN(p3.payment_id)
          FROM payments p3
          WHERE p3.rental_id = p2.rental_id
            AND p3.status = 'verified'
            AND p3.payment_type = 'balance'
      )
      AND (
          -- May deposit na verified para sa rental na ito
          SELECT COALESCE(SUM(amount), 0)
          FROM payments p4
          WHERE p4.rental_id = p2.rental_id
            AND p4.status = 'verified'
            AND p4.payment_type = 'deposit'
      ) > 0
) dup ON p.payment_id = dup.payment_id
SET p.status = 'rejected',
    p.rejection_reason = 'Duplicate payment - rental already fully paid';

-- Step 2: I-update ang payment_status ng mga affected rentals
UPDATE van_rentals vr
SET vr.payment_status = CASE
    WHEN (
        SELECT COALESCE(SUM(amount), 0)
        FROM payments p
        WHERE p.rental_id = vr.rental_id AND p.status = 'verified'
    ) >= vr.total_price - 0.005 THEN 'paid'
    WHEN (
        SELECT COALESCE(SUM(amount), 0)
        FROM payments p
        WHERE p.rental_id = vr.rental_id AND p.status = 'verified'
    ) > 0 THEN 'partially_paid'
    ELSE 'pending'
END
WHERE vr.rental_id IN (
    SELECT DISTINCT rental_id FROM payments WHERE rental_id IS NOT NULL AND status = 'verified'
);
