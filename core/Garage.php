<?php
namespace core;
class Garage{
	public static function getQuery(){
        return "
            SELECT
                g.user_id,
                g.modification_id,
                g.title AS title_garage,
                g.is_active,
                g.year,
                g.owner,
                g.phone,
                m.model_id,
                m.title AS title_modification,
                md.vin,
                md.title AS title_model,
                b.title AS title_brend,
                md.vehicle_id
            FROM
                #garage g
            LEFT JOIN
                #modifications m ON m.id=g.modification_id
            LEFT JOIN
                #models md ON md.id=m.model_id
            LEFT JOIN
                #vehicle_model_fvs fvs ON fvs.modification_id=g.modification_id
            LEFT JOIN
                #vehicle_filter_values fv ON fv.id=fvs.fv_id
            LEFT JOIN
                #vehicle_filters f ON f.id=fv.filter_id AND f.title='Год'
            LEFT JOIN
                #brends b ON b.id=md.brend_id

        ";
    }
}
