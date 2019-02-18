# (1) Update schema patch table, remove old version and add current version.

# Different implementations are possible, e.g. we could test that the current schema is 
# the schema that this patch updates from, and only apply the patch in that case.
# Delete all, then insert new row is current expectation of OmOccurrences.checkSchema().


 


# (2) Put schema changes here.



DELIMITER //

CREATE PROCEDURE tempUpdateSchema() 

BEGIN

    SET @thisVersion = '0.9.1.13';

    # Run this patch only if not previously applied
    SET @testThisVersion := (SELECT versionnumber FROM schemaversion WHERE versionnumber = @thisVersion);
    IF @testThisVersion IS NULL THEN

        INSERT INTO schemaversion (versionnumber) values (@thisVersion);

        # Insert patch statements below








    END IF;

END;
//

DELIMITER ;

# Execute the procedure
CALL tempUpdateSchema();

# Drop the procedure
DROP PROCEDURE tempUpdateSchema;