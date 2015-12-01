#Piramide API

## Methods

### update_outs/:kpiid

Creates a connection with the database and pulls the quantity of outs for a stored group of `codes` given the `kpiid`.

If no `kpiid` is supplied returns error

#### Arguments
* __:kpiid__ _string_
    - The `number` associated with the KPI stored in the database
    - `http://localhost/piremide/toolbox.php/update_outs/2`

#### Return Value
___Object___: {error:[true|false]}

### /list

Returns the list of available `kpi`'s so you can query the data of any of them

#### Return Value
    ___Object___:{
        name:_string_,
        color:_string_,
        id:_number_,
        Area:_string_,
        type:_string_,
        title_offset_x:_number_,
        title:offset_y:_number_
    }

