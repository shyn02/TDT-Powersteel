/* =============================================================
   STEEL WEIGHT CALCULATOR — TDT Powersteel Corporation
   Pure client-side JS, no server calls needed.
   ============================================================= */
(function () {
    'use strict';

    var STEEL_DENSITY = 7850; // kg/m³
    var LB_PER_KG = 2.20462;

    /* ---------- Product-name → calculator-type mapping ---------- */
    /*  Each entry: key = product name (as stored in DB, case-insensitive match),
        value = calc type.  If a product isn't listed, we fall back to the
        category-slug mapping below.                                     */
    var PRODUCT_TYPE_MAP = {
        /* Steel Bars */
        'deformed round bar':      'round_bar',
        'plain round bar':         'round_bar',
        'flat bar':                'flat_bar',
        'square bar':              'square_bar',
        'angle bar':               'angle_bar',

        /* Columns & Beams */
        'channel bar':             'beam',
        'i-bar':                   'beam',
        'i-beam':                  'beam',
        't-bar':                   'beam',
        'z-bar':                   'beam',
        'wide flange':             'beam',

        /* Sheet Pile */
        'sheet pile':              'sheet_pile',

        /* Shafting */
        'cold rolled shafting':    'round_bar',
        'crs':                     'round_bar',
        'tool steel shafting':     'round_bar',

        /* Plates & Sheets */
        'mild steel plate':        'plate',
        'boiler plate':            'plate',
        'armored plate':           'plate',
        'mild steel checkered plate': 'plate',
        'checkered plate':         'plate',
        'galvanized iron sheet':   'sheet',
        'gi sheet':                'sheet',
        'black iron sheet':        'sheet',

        /* Tubes & Pipes */
        'square tube':             'tube',
        'round tube':              'tube',
        'rectangular tube':        'tube',
        'galvanized iron pipe':    'pipe',
        'gi pipe':                 'pipe',
        'black iron pipe':         'pipe',
        'boiler tube':             'tube',

        /* Steel Purlins */
        'c-purlins':               'purlin',
        'z-purlins':               'purlin',
        'c purlins':               'purlin',
        'z purlins':               'purlin',
        'c-purlin':                'purlin',
        'z-purlin':                'purlin',

        /* Wire Mesh */
        'welded wire mesh':        'wire_mesh',
        'wire mesh':               'wire_mesh',

        /* Roofing */
        'insulated roof panels':   'roofing',
        'insulated wall panels':   'roofing',
        'stone rib':               'roofing',
    };

    /* Category-slug fallback (when product name isn't in the map above) */
    var CATEGORY_TYPE_MAP = {
        'steel-bars':        'round_bar',
        'columns-beams':     'beam',
        'wide-flange':       'beam',
        'sheet-pile':        'sheet_pile',
        'shafting':          'round_bar',
        'plates-sheets':     'plate',
        'tubes-pipes':       'pipe',
        'steel-purlins':     'purlin',
        'wiremesh':          'wire_mesh',
        'roofing':           'roofing',
        'hardware':          null,
        'construction-materials': null,
    };

    /* ---------- Beam / Purlin / Sheet-Pile size presets ---------- */
    var BEAM_SIZES = [
        { label: 'I-Beam 4"',    weight: 7.78 },
        { label: 'I-Beam 6"',    weight: 14.9 },
        { label: 'I-Beam 8"',    weight: 18.4 },
        { label: 'I-Beam 10"',   weight: 25.4 },
        { label: 'I-Beam 12"',   weight: 31.1 },
        { label: 'Channel 3"',   weight: 4.30 },
        { label: 'Channel 4"',   weight: 5.83 },
        { label: 'Channel 5"',   weight: 7.13 },
        { label: 'Channel 6"',   weight: 8.53 },
        { label: 'Channel 8"',   weight: 11.5 },
        { label: 'T-Bar 3x3',    weight: 5.42 },
        { label: 'T-Bar 4x4',    weight: 7.22 },
        { label: 'Z-Bar',        weight: 6.80 },
    ];

    var PURLIN_SIZES = [
        { label: 'C-Purlin 2x3x0.60',  weight: 0.60 },
        { label: 'C-Purlin 2x4x0.60',  weight: 0.72 },
        { label: 'C-Purlin 2x6x0.60',  weight: 0.96 },
        { label: 'C-Purlin 3x4x0.60',  weight: 0.84 },
        { label: 'C-Purlin 3x6x0.60',  weight: 1.08 },
        { label: 'C-Purlin 3x8x0.60',  weight: 1.32 },
        { label: 'C-Purlin 3x10x0.60', weight: 1.56 },
        { label: 'C-Purlin 4x8x0.60',  weight: 1.44 },
        { label: 'C-Purlin 4x10x0.60', weight: 1.68 },
        { label: 'Z-Purlin 2x6x0.60',  weight: 0.96 },
        { label: 'Z-Purlin 3x6x0.60',  weight: 1.08 },
        { label: 'Z-Purlin 3x8x0.60',  weight: 1.32 },
        { label: 'Z-Purlin 4x8x0.60',  weight: 1.44 },
    ];

    var SHEET_PILE_TYPES = [
        { label: 'Type 2 (400mm, 48 kg/m)',  weightPerM: 48 },
        { label: 'Type 3 (400mm, 60 kg/m)',  weightPerM: 60 },
        { label: 'Type 4 (400mm, 76.1 kg/m)', weightPerM: 76.1 },
    ];

    /* ---------- Form HTML builders per calculator type ---------- */

    function buildRoundBarForm() {
        return '<div class="form-row">' +
            '<div class="form-group">' +
                '<label>Diameter</label>' +
                '<input type="number" id="calcDiameter" placeholder="e.g. 12" min="1" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<input type="number" id="calcLength" placeholder="e.g. 6" min="0.1" step="any">' +
                '<div class="unit-hint">meters</div>' +
            '</div>' +
        '</div>' +
        '<div class="form-group">' +
            '<label>Quantity</label>' +
            '<input type="number" id="calcQty" placeholder="e.g. 10" min="1" value="1">' +
            '<div class="unit-hint">pcs</div>' +
        '</div>';
    }

    function buildFlatBarForm() {
        return '<div class="form-row three-col">' +
            '<div class="form-group">' +
                '<label>Width</label>' +
                '<input type="number" id="calcWidth" placeholder="e.g. 50" min="1" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Thickness</label>' +
                '<input type="number" id="calcThickness" placeholder="e.g. 6" min="0.5" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<input type="number" id="calcLength" placeholder="e.g. 6" min="0.1" step="any">' +
                '<div class="unit-hint">meters</div>' +
            '</div>' +
        '</div>' +
        '<div class="form-group">' +
            '<label>Quantity</label>' +
            '<input type="number" id="calcQty" placeholder="e.g. 10" min="1" value="1">' +
            '<div class="unit-hint">pcs</div>' +
        '</div>';
    }

    function buildSquareBarForm() {
        return '<div class="form-row">' +
            '<div class="form-group">' +
                '<label>Side</label>' +
                '<input type="number" id="calcDiameter" placeholder="e.g. 25" min="1" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<input type="number" id="calcLength" placeholder="e.g. 6" min="0.1" step="any">' +
                '<div class="unit-hint">meters</div>' +
            '</div>' +
        '</div>' +
        '<div class="form-group">' +
            '<label>Quantity</label>' +
            '<input type="number" id="calcQty" placeholder="e.g. 10" min="1" value="1">' +
            '<div class="unit-hint">pcs</div>' +
        '</div>';
    }

    function buildAngleBarForm() {
        return '<div class="form-row three-col">' +
            '<div class="form-group">' +
                '<label>Leg A</label>' +
                '<input type="number" id="calcLegA" placeholder="e.g. 50" min="1" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Leg B</label>' +
                '<input type="number" id="calcLegB" placeholder="e.g. 50" min="1" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Thickness</label>' +
                '<input type="number" id="calcThickness" placeholder="e.g. 5" min="0.5" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
        '</div>' +
        '<div class="form-row">' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<input type="number" id="calcLength" placeholder="e.g. 6" min="0.1" step="any">' +
                '<div class="unit-hint">meters</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Quantity</label>' +
                '<input type="number" id="calcQty" placeholder="e.g. 10" min="1" value="1">' +
                '<div class="unit-hint">pcs</div>' +
            '</div>' +
        '</div>';
    }

    function buildPlateForm() {
        return '<div class="form-row three-col">' +
            '<div class="form-group">' +
                '<label>Thickness</label>' +
                '<input type="number" id="calcThickness" placeholder="e.g. 10" min="0.5" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Width</label>' +
                '<input type="number" id="calcWidth" placeholder="e.g. 1200" min="1" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<input type="number" id="calcLength" placeholder="e.g. 2400" min="1" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
        '</div>' +
        '<div class="form-group">' +
            '<label>Quantity</label>' +
            '<input type="number" id="calcQty" placeholder="e.g. 5" min="1" value="1">' +
            '<div class="unit-hint">pcs</div>' +
        '</div>';
    }

    function buildSheetForm() {
        return buildPlateForm();
    }

    function buildPipeForm() {
        return '<div class="form-row three-col">' +
            '<div class="form-group">' +
                '<label>Outer Diameter</label>' +
                '<input type="number" id="calcOD" placeholder="e.g. 48.3" min="1" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Wall Thickness</label>' +
                '<input type="number" id="calcThickness" placeholder="e.g. 3.2" min="0.5" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<input type="number" id="calcLength" placeholder="e.g. 6" min="0.1" step="any">' +
                '<div class="unit-hint">meters</div>' +
            '</div>' +
        '</div>' +
        '<div class="form-group">' +
            '<label>Quantity</label>' +
            '<input type="number" id="calcQty" placeholder="e.g. 20" min="1" value="1">' +
            '<div class="unit-hint">pcs</div>' +
        '</div>';
    }

    function buildTubeForm() {
        return '<div class="form-row">' +
            '<div class="form-group">' +
                '<label>Width</label>' +
                '<input type="number" id="calcWidth" placeholder="e.g. 50" min="1" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Height</label>' +
                '<input type="number" id="calcHeight" placeholder="e.g. 50" min="1" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
        '</div>' +
        '<div class="form-row three-col">' +
            '<div class="form-group">' +
                '<label>Wall Thickness</label>' +
                '<input type="number" id="calcThickness" placeholder="e.g. 2.3" min="0.5" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<input type="number" id="calcLength" placeholder="e.g. 6" min="0.1" step="any">' +
                '<div class="unit-hint">meters</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Quantity</label>' +
                '<input type="number" id="calcQty" placeholder="e.g. 10" min="1" value="1">' +
                '<div class="unit-hint">pcs</div>' +
            '</div>' +
        '</div>';
    }

    function buildBeamForm() {
        var opts = '<option value="">-- Select Size --</option>';
        BEAM_SIZES.forEach(function (s, i) {
            opts += '<option value="' + i + '">' + s.label + ' (' + s.weight + ' kg/m)</option>';
        });
        return '<div class="form-group">' +
            '<label>Size</label>' +
            '<select id="calcSize">' + opts + '</select>' +
        '</div>' +
        '<div class="form-row">' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<input type="number" id="calcLength" placeholder="e.g. 6" min="0.1" step="any">' +
                '<div class="unit-hint">meters</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Quantity</label>' +
                '<input type="number" id="calcQty" placeholder="e.g. 5" min="1" value="1">' +
                '<div class="unit-hint">pcs</div>' +
            '</div>' +
        '</div>';
    }

    function buildPurlinForm() {
        var opts = '<option value="">-- Select Size --</option>';
        PURLIN_SIZES.forEach(function (s, i) {
            opts += '<option value="' + i + '">' + s.label + ' (' + s.weight + ' kg/m)</option>';
        });
        return '<div class="form-group">' +
            '<label>Size</label>' +
            '<select id="calcSize">' + opts + '</select>' +
        '</div>' +
        '<div class="form-row">' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<input type="number" id="calcLength" placeholder="e.g. 6" min="0.1" step="any">' +
                '<div class="unit-hint">meters</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Quantity</label>' +
                '<input type="number" id="calcQty" placeholder="e.g. 20" min="1" value="1">' +
                '<div class="unit-hint">pcs</div>' +
            '</div>' +
        '</div>';
    }

    function buildSheetPileForm() {
        var opts = '<option value="">-- Select Type --</option>';
        SHEET_PILE_TYPES.forEach(function (s, i) {
            opts += '<option value="' + i + '">' + s.label + '</option>';
        });
        return '<div class="form-group">' +
            '<label>Type</label>' +
            '<select id="calcSize">' + opts + '</select>' +
        '</div>' +
        '<div class="form-group">' +
            '<label>Length</label>' +
            '<input type="number" id="calcLength" placeholder="e.g. 12" min="0.1" step="any">' +
            '<div class="unit-hint">meters</div>' +
        '</div>';
    }

    function buildWireMeshForm() {
        return '<div class="form-row three-col">' +
            '<div class="form-group">' +
                '<label>Thickness</label>' +
                '<input type="number" id="calcThickness" placeholder="e.g. 3" min="0.5" step="any">' +
                '<div class="unit-hint">mm (wire dia)</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Width</label>' +
                '<input type="number" id="calcWidth" placeholder="e.g. 1200" min="1" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<input type="number" id="calcLength" placeholder="e.g. 2400" min="1" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
        '</div>' +
        '<div class="form-group">' +
            '<label>Sheet Quantity</label>' +
            '<input type="number" id="calcQty" placeholder="e.g. 10" min="1" value="1">' +
            '<div class="unit-hint">sheets</div>' +
        '</div>';
    }

    function buildRoofingForm() {
        return '<div class="form-row three-col">' +
            '<div class="form-group">' +
                '<label>Thickness</label>' +
                '<input type="number" id="calcThickness" placeholder="e.g. 0.5" min="0.1" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Width</label>' +
                '<input type="number" id="calcWidth" placeholder="e.g. 1000" min="1" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<input type="number" id="calcLength" placeholder="e.g. 3000" min="1" step="any">' +
                '<div class="unit-hint">mm</div>' +
            '</div>' +
        '</div>' +
        '<div class="form-group">' +
            '<label>Quantity</label>' +
            '<input type="number" id="calcQty" placeholder="e.g. 20" min="1" value="1">' +
            '<div class="unit-hint">pcs</div>' +
        '</div>';
    }

    function buildUnsupportedForm() {
        return '<p style="color:#999; font-size:13px; text-align:center; padding:20px 0;">Weight calculator is not available for this product. Please <strong>Request a Quote</strong> and our team will provide weight estimates with your quotation.</p>';
    }

    /* ---------- Calculation functions ---------- */

    function calcRoundBar() {
        var d = parseFloat(document.getElementById('calcDiameter').value);
        var L = parseFloat(document.getElementById('calcLength').value);
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!d || !L) return null;
        // W (kg) = d² × 0.006165 × L × qty
        var perPc = d * d * 0.006165 * L;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg' };
    }

    function calcFlatBar() {
        var w = parseFloat(document.getElementById('calcWidth').value);
        var t = parseFloat(document.getElementById('calcThickness').value);
        var L = parseFloat(document.getElementById('calcLength').value);
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!w || !t || !L) return null;
        // W (kg) = w(mm) × t(mm) × L(m) × 7850 / 1,000,000 × qty
        var perPc = w * t * L * STEEL_DENSITY / 1000000;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg' };
    }

    function calcSquareBar() {
        var d = parseFloat(document.getElementById('calcDiameter').value);
        var L = parseFloat(document.getElementById('calcLength').value);
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!d || !L) return null;
        var perPc = d * d * L * STEEL_DENSITY / 1000000;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg' };
    }

    function calcAngleBar() {
        var a = parseFloat(document.getElementById('calcLegA').value);
        var b = parseFloat(document.getElementById('calcLegB').value);
        var t = parseFloat(document.getElementById('calcThickness').value);
        var L = parseFloat(document.getElementById('calcLength').value);
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!a || !b || !t || !L) return null;
        // Approximate: area = (a + b - t) × t
        var perPc = (a + b - t) * t * L * STEEL_DENSITY / 1000000;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg' };
    }

    function calcPlate() {
        var t = parseFloat(document.getElementById('calcThickness').value);
        var w = parseFloat(document.getElementById('calcWidth').value);
        var L = parseFloat(document.getElementById('calcLength').value);
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!t || !w || !L) return null;
        // All in mm: W (kg) = T × W × L × 7850 / 1e9 × qty
        var perPc = t * w * L * STEEL_DENSITY / 1000000000;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg' };
    }

    function calcSheet() { return calcPlate(); }

    function calcPipe() {
        var od = parseFloat(document.getElementById('calcOD').value);
        var t = parseFloat(document.getElementById('calcThickness').value);
        var L = parseFloat(document.getElementById('calcLength').value);
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!od || !t || !L) return null;
        // W (kg/m) = (OD - T) × T × 0.02466
        var perM = (od - t) * t * 0.02466;
        var perPc = perM * L;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg' };
    }

    function calcTube() {
        var w = parseFloat(document.getElementById('calcWidth').value);
        var h = parseFloat(document.getElementById('calcHeight').value);
        var t = parseFloat(document.getElementById('calcThickness').value);
        var L = parseFloat(document.getElementById('calcLength').value);
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!w || !h || !t || !L) return null;
        // W (kg/m) = (W + H - 2T) × 2T × 0.0157
        var perM = (w + h - 2 * t) * 2 * t * 0.0157;
        var perPc = perM * L;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg' };
    }

    function calcBeam() {
        var idx = document.getElementById('calcSize').value;
        var L = parseFloat(document.getElementById('calcLength').value);
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (idx === '' || !L) return null;
        var unitW = BEAM_SIZES[parseInt(idx)].weight;
        var perPc = unitW * L;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg', breakdown: unitW + ' kg/m × ' + L + ' m' };
    }

    function calcPurlin() {
        var idx = document.getElementById('calcSize').value;
        var L = parseFloat(document.getElementById('calcLength').value);
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (idx === '' || !L) return null;
        var unitW = PURLIN_SIZES[parseInt(idx)].weight;
        var perPc = unitW * L;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg', breakdown: unitW + ' kg/m × ' + L + ' m' };
    }

    function calcSheetPile() {
        var idx = document.getElementById('calcSize').value;
        var L = parseFloat(document.getElementById('calcLength').value);
        if (idx === '' || !L) return null;
        var perM = SHEET_PILE_TYPES[parseInt(idx)].weightPerM;
        var total = perM * L;
        return { perPiece: total, total: total, qty: 1, unit: 'kg', breakdown: perM + ' kg/m × ' + L + ' m' };
    }

    function calcWireMesh() {
        var t = parseFloat(document.getElementById('calcThickness').value);
        var w = parseFloat(document.getElementById('calcWidth').value);
        var L = parseFloat(document.getElementById('calcLength').value);
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!t || !w || !L) return null;
        // Rough estimate: wire_dia² × total_wire_length × density
        // Simplified: area(m²) × wire_dia(mm)² × 0.00617 × est_wires_per_m
        var areaM2 = (w / 1000) * (L / 1000);
        var perSheet = areaM2 * t * t * 0.00617 * 40;
        return { perPiece: perSheet, total: perSheet * q, qty: q, unit: 'kg', note: 'Approximate weight' };
    }

    function calcRoofing() {
        var t = parseFloat(document.getElementById('calcThickness').value);
        var w = parseFloat(document.getElementById('calcWidth').value);
        var L = parseFloat(document.getElementById('calcLength').value);
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!t || !w || !L) return null;
        var perPc = t * w * L * STEEL_DENSITY / 1000000000;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg' };
    }

    /* ---------- Type → builder/calculator mapping ---------- */

    var TYPE_CONFIG = {
        'round_bar':  { form: buildRoundBarForm,  calc: calcRoundBar },
        'flat_bar':   { form: buildFlatBarForm,   calc: calcFlatBar },
        'square_bar': { form: buildSquareBarForm, calc: calcSquareBar },
        'angle_bar':  { form: buildAngleBarForm,  calc: calcAngleBar },
        'plate':      { form: buildPlateForm,     calc: calcPlate },
        'sheet':      { form: buildSheetForm,     calc: calcSheet },
        'pipe':       { form: buildPipeForm,      calc: calcPipe },
        'tube':       { form: buildTubeForm,      calc: calcTube },
        'beam':       { form: buildBeamForm,      calc: calcBeam },
        'purlin':     { form: buildPurlinForm,    calc: calcPurlin },
        'sheet_pile': { form: buildSheetPileForm, calc: calcSheetPile },
        'wire_mesh':  { form: buildWireMeshForm,  calc: calcWireMesh },
        'roofing':    { form: buildRoofingForm,    calc: calcRoofing },
    };

    /* ---------- Resolve product name + category slug → type ---------- */

    function resolveType(productName, categorySlug) {
        var nameLower = (productName || '').toLowerCase().trim();
        if (PRODUCT_TYPE_MAP[nameLower]) return PRODUCT_TYPE_MAP[nameLower];
        var slugLower = (categorySlug || '').toLowerCase().trim();
        return CATEGORY_TYPE_MAP[slugLower] || null;
    }

    /* ---------- Format number ---------- */

    function fmt(n) {
        if (n >= 1000) return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return n.toFixed(2);
    }

    /* ---------- Wire everything up on DOMContentLoaded ---------- */

    document.addEventListener('DOMContentLoaded', function () {
        var overlay = document.getElementById('calcModalOverlay');
        var modal = overlay ? overlay.querySelector('.calc-modal') : null;
        var formContainer = document.getElementById('calcFormContainer');
        var productNameEl = document.getElementById('calcProductName');
        var resultEl = document.getElementById('calcResult');
        var resultWeightEl = document.getElementById('calcResultWeight');
        var resultSecondaryEl = document.getElementById('calcResultSecondary');
        var resultBreakdownEl = document.getElementById('calcResultBreakdown');

        var currentType = null;
        var currentCalcFn = null;

        function openCalcModal(productName, categorySlug) {
            var type = resolveType(productName, categorySlug);
            currentType = type;

            if (!type || !TYPE_CONFIG[type]) {
                formContainer.innerHTML = buildUnsupportedForm();
                productNameEl.textContent = productName || '';
                resultEl.classList.remove('show');
                overlay.classList.add('active');
                document.body.style.overflowY = 'hidden';
                return;
            }

            currentCalcFn = TYPE_CONFIG[type].calc;
            formContainer.innerHTML = TYPE_CONFIG[type].form();
            productNameEl.textContent = productName || '';
            resultEl.classList.remove('show');
            overlay.classList.add('active');
            document.body.style.overflowY = 'hidden';
        }

        function closeCalcModal() {
            overlay.classList.remove('active');
            document.body.style.overflowY = '';
            formContainer.innerHTML = '';
            resultEl.classList.remove('show');
            currentType = null;
            currentCalcFn = null;
        }

        /* Button clicks */
        document.querySelectorAll('.btn-calc-trigger').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var name = btn.getAttribute('data-product-name') || '';
                var slug = btn.getAttribute('data-category-slug') || '';
                openCalcModal(name, slug);
            });
        });

        /* Close button */
        var closeBtn = document.getElementById('calcClose');
        if (closeBtn) closeBtn.addEventListener('click', closeCalcModal);

        /* Click outside to close */
        if (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) closeCalcModal();
            });
        }

        /* ESC to close */
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay && overlay.classList.contains('active')) {
                closeCalcModal();
            }
        });

        /* Calculate button */
        var calcBtn = document.getElementById('calcCalcBtn');
        if (calcBtn) {
            calcBtn.addEventListener('click', function () {
                if (!currentCalcFn) return;
                var result = currentCalcFn();
                if (!result) {
                    resultEl.classList.remove('show');
                    return;
                }

                var totalKg = result.total;
                var totalLb = totalKg * LB_PER_KG;
                resultWeightEl.innerHTML = fmt(totalKg) + ' <span>kg</span>';
                resultSecondaryEl.textContent = '≈ ' + fmt(totalLb) + ' lbs';

                var breakdown = '';
                if (result.breakdown) breakdown += result.breakdown + '<br>';
                if (result.qty > 1) breakdown += 'Weight per piece: ' + fmt(result.perPiece) + ' kg';
                if (result.note) breakdown += (breakdown ? '<br>' : '') + result.note;
                resultBreakdownEl.innerHTML = breakdown;

                resultEl.classList.add('show');
            });
        }

        /* Reset button */
        var resetBtn = document.getElementById('calcResetBtn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                formContainer.querySelectorAll('input').forEach(function (input) { input.value = ''; });
                formContainer.querySelectorAll('select').forEach(function (sel) { sel.selectedIndex = 0; });
                resultEl.classList.remove('show');
            });
        }

        /* "Request a Quote" from calculator */
        var quoteBtn = document.getElementById('calcQuoteBtn');
        if (quoteBtn) {
            quoteBtn.addEventListener('click', function () {
                closeCalcModal();
                var quoteTrigger = document.querySelector('.btn-quote-trigger[data-product]');
                if (quoteTrigger) quoteTrigger.click();
            });
        }

        /* ============================================
           HOMEPAGE WEIGHT CALCULATOR
           ============================================ */
        var homeCalcSelect = document.getElementById('homeCalcProduct');
        var homeCalcForm = document.getElementById('homeCalcFormContainer');
        var homeCalcResult = document.getElementById('homeCalcResult');
        var homeCalcWeightEl = document.getElementById('homeCalcResultWeight');
        var homeCalcSecondaryEl = document.getElementById('homeCalcResultSecondary');
        var homeCalcBreakdownEl = document.getElementById('homeCalcResultBreakdown');
        var homeCalcCalcFn = null;

        if (homeCalcSelect) {
            homeCalcSelect.addEventListener('change', function () {
                var type = this.value;
                homeCalcResult.classList.remove('show');
                if (!type || !TYPE_CONFIG[type]) {
                    homeCalcForm.innerHTML = '';
                    homeCalcCalcFn = null;
                    return;
                }
                homeCalcCalcFn = TYPE_CONFIG[type].calc;
                homeCalcForm.innerHTML = TYPE_CONFIG[type].form();
            });
        }

        var homeCalcBtn = document.getElementById('homeCalcBtn');
        if (homeCalcBtn) {
            homeCalcBtn.addEventListener('click', function () {
                if (!homeCalcCalcFn) return;
                var result = homeCalcCalcFn();
                if (!result) {
                    homeCalcResult.classList.remove('show');
                    return;
                }
                var totalKg = result.total;
                var totalLb = totalKg * LB_PER_KG;
                homeCalcWeightEl.innerHTML = fmt(totalKg) + ' <span>kg</span>';
                homeCalcSecondaryEl.textContent = '\u2248 ' + fmt(totalLb) + ' lbs';

                var breakdown = '';
                if (result.breakdown) breakdown += result.breakdown + '<br>';
                if (result.qty > 1) breakdown += 'Weight per piece: ' + fmt(result.perPiece) + ' kg';
                if (result.note) breakdown += (breakdown ? '<br>' : '') + result.note;
                homeCalcBreakdownEl.innerHTML = breakdown;

                homeCalcResult.classList.add('show');
            });
        }

        var homeCalcReset = document.getElementById('homeCalcResetBtn');
        if (homeCalcReset) {
            homeCalcReset.addEventListener('click', function () {
                if (homeCalcForm) {
                    homeCalcForm.querySelectorAll('input').forEach(function (i) { i.value = ''; });
                    homeCalcForm.querySelectorAll('select').forEach(function (s) { s.selectedIndex = 0; });
                }
                homeCalcResult.classList.remove('show');
            });
        }
    });
})();
