/* =============================================================
   STEEL WEIGHT CALCULATOR — TDT Powersteel Corporation
   Pure client-side JS, no server calls needed.
   ============================================================= */
(function () {
    'use strict';

    var STEEL_DENSITY = 7850; // kg/m³
    var LB_PER_KG = 2.20462;

    /* ---------- Unit conversion helpers ---------- */
    /*  Cross-section inputs: base = mm */
    var DIM_TO_MM = { 'mm': 1, 'cm': 10, 'in': 25.4, 'ft': 304.8 };
    var DIM_UNITS = ['mm', 'cm', 'in', 'ft'];
    var DIM_PLACEHOLDERS = { 'mm': 'e.g. 12', 'cm': 'e.g. 1.2', 'in': 'e.g. 0.5', 'ft': 'e.g. 0.04' };
    /*  Length inputs: base = m */
    var LEN_TO_M = { 'm': 1, 'ft': 0.3048, 'yd': 0.9144, 'in': 0.0254 };
    var LEN_UNITS = ['m', 'ft', 'yd', 'in'];
    var LEN_PLACEHOLDERS = { 'm': 'e.g. 6', 'ft': 'e.g. 20', 'yd': 'e.g. 22', 'in': 'e.g. 240' };

    /* Global unit state — shared across both calculators */
    var currentDimUnit = 'mm';
    var currentLenUnit = 'm';

    function dimUnitSelect(id) {
        var h = '<select id="' + id + '-unit" class="calc-unit-select">';
        DIM_UNITS.forEach(function (u) {
            h += '<option value="' + u + '"' + (u === currentDimUnit ? ' selected' : '') + '>' + u + '</option>';
        });
        return h + '</select>';
    }

    function lenUnitSelect(id) {
        var h = '<select id="' + id + '-unit" class="calc-unit-select">';
        LEN_UNITS.forEach(function (u) {
            h += '<option value="' + u + '"' + (u === currentLenUnit ? ' selected' : '') + '>' + u + '</option>';
        });
        return h + '</select>';
    }

    function toMm(val) { return val * DIM_TO_MM[currentDimUnit]; }
    function toM(val)   { return val * LEN_TO_M[currentLenUnit]; }

    function dimPlaceholder() { return DIM_PLACEHOLDERS[currentDimUnit]; }
    function lenPlaceholder() { return LEN_PLACEHOLDERS[currentLenUnit]; }
    function dimHint() { return currentDimUnit; }
    function lenHint() { return currentLenUnit; }

    /* ---------- Product-name → calculator-type mapping ---------- */
    var PRODUCT_TYPE_MAP = {
        'deformed round bar':      'round_bar',
        'plain round bar':         'round_bar',
        'flat bar':                'flat_bar',
        'square bar':              'square_bar',
        'angle bar':               'angle_bar',
        'channel bar':             'beam',
        'i-bar':                   'beam',
        'i-beam':                  'beam',
        't-bar':                   'beam',
        'z-bar':                   'beam',
        'wide flange':             'beam',
        'sheet pile':              'sheet_pile',
        'cold rolled shafting':    'round_bar',
        'crs':                     'round_bar',
        'tool steel shafting':     'round_bar',
        'mild steel plate':        'plate',
        'boiler plate':            'plate',
        'armored plate':           'plate',
        'mild steel checkered plate': 'plate',
        'checkered plate':         'plate',
        'galvanized iron sheet':   'sheet',
        'gi sheet':                'sheet',
        'black iron sheet':        'sheet',
        'square tube':             'tube',
        'round tube':              'tube',
        'rectangular tube':        'tube',
        'galvanized iron pipe':    'pipe',
        'gi pipe':                 'pipe',
        'black iron pipe':         'pipe',
        'boiler tube':             'tube',
        'c-purlins':               'purlin',
        'z-purlins':               'purlin',
        'c purlins':               'purlin',
        'z purlins':               'purlin',
        'c-purlin':                'purlin',
        'z-purlin':                'purlin',
        'welded wire mesh':        'wire_mesh',
        'wire mesh':               'wire_mesh',
        'insulated roof panels':   'roofing',
        'insulated wall panels':   'roofing',
        'stone rib':               'roofing',
    };

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

    /* ---------- Unit toggle HTML (shared) ---------- */
    /* Types that use a Size/Type preset dropdown instead of cross-section dimension inputs */
    var PRESET_ONLY_TYPES = { 'beam': true, 'purlin': true, 'sheet_pile': true };

    function unitToggleHTML(type) {
        var dim = '<select class="calc-unit-select calc-dim-unit">' +
            DIM_UNITS.map(function (u) { return '<option value="' + u + '"' + (u === currentDimUnit ? ' selected' : '') + '>' + u + ' (cross-section)</option>'; }).join('') +
            '</select>';
        var len = '<select class="calc-unit-select calc-len-unit">' +
            LEN_UNITS.map(function (u) { return '<option value="' + u + '"' + (u === currentLenUnit ? ' selected' : '') + '>' + u + ' (length)</option>'; }).join('') +
            '</select>';
        return '<div class="calc-unit-bar-inner">' +
            '<label>Units:</label>' +
            (PRESET_ONLY_TYPES[type] ? '' : dim) +
            len +
        '</div>';
    }

    /* ---------- Form HTML builders per calculator type ---------- */

    function buildRoundBarForm() {
        return '<div class="form-row">' +
            '<div class="form-group">' +
                '<label>Diameter</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcDiameter" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcDiameter') +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcLength" placeholder="' + lenPlaceholder() + '" min="0.01" step="any">' +
                    lenUnitSelect('calcLength') +
                '</div>' +
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
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcWidth" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcWidth') +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Thickness</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcThickness" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcThickness') +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcLength" placeholder="' + lenPlaceholder() + '" min="0.01" step="any">' +
                    lenUnitSelect('calcLength') +
                '</div>' +
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
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcDiameter" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcDiameter') +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcLength" placeholder="' + lenPlaceholder() + '" min="0.01" step="any">' +
                    lenUnitSelect('calcLength') +
                '</div>' +
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
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcLegA" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcLegA') +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Leg B</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcLegB" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcLegB') +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Thickness</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcThickness" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcThickness') +
                '</div>' +
            '</div>' +
        '</div>' +
        '<div class="form-row">' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcLength" placeholder="' + lenPlaceholder() + '" min="0.01" step="any">' +
                    lenUnitSelect('calcLength') +
                '</div>' +
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
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcThickness" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcThickness') +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Width</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcWidth" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcWidth') +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcLength" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcLength') +
                '</div>' +
            '</div>' +
        '</div>' +
        '<div class="form-group">' +
            '<label>Quantity</label>' +
            '<input type="number" id="calcQty" placeholder="e.g. 5" min="1" value="1">' +
            '<div class="unit-hint">pcs</div>' +
        '</div>';
    }

    function buildSheetForm() { return buildPlateForm(); }

    function buildPipeForm() {
        return '<div class="form-row three-col">' +
            '<div class="form-group">' +
                '<label>Outer Diameter</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcOD" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcOD') +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Wall Thickness</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcThickness" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcThickness') +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcLength" placeholder="' + lenPlaceholder() + '" min="0.01" step="any">' +
                    lenUnitSelect('calcLength') +
                '</div>' +
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
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcWidth" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcWidth') +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Height</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcHeight" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcHeight') +
                '</div>' +
            '</div>' +
        '</div>' +
        '<div class="form-row three-col">' +
            '<div class="form-group">' +
                '<label>Wall Thickness</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcThickness" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcThickness') +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcLength" placeholder="' + lenPlaceholder() + '" min="0.01" step="any">' +
                    lenUnitSelect('calcLength') +
                '</div>' +
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
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcLength" placeholder="' + lenPlaceholder() + '" min="0.01" step="any">' +
                    lenUnitSelect('calcLength') +
                '</div>' +
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
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcLength" placeholder="' + lenPlaceholder() + '" min="0.01" step="any">' +
                    lenUnitSelect('calcLength') +
                '</div>' +
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
            '<div class="calc-input-row">' +
                '<input type="number" id="calcLength" placeholder="' + lenPlaceholder() + '" min="0.01" step="any">' +
                lenUnitSelect('calcLength') +
            '</div>' +
        '</div>';
    }

    function buildWireMeshForm() {
        return '<div class="form-row three-col">' +
            '<div class="form-group">' +
                '<label>Thickness</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcThickness" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcThickness') +
                '</div>' +
                '<div class="unit-hint">wire dia</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Width</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcWidth" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcWidth') +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcLength" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcLength') +
                '</div>' +
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
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcThickness" placeholder="' + dimPlaceholder() + '" min="0.01" step="any">' +
                    dimUnitSelect('calcThickness') +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Width</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcWidth" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcWidth') +
                '</div>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Length</label>' +
                '<div class="calc-input-row">' +
                    '<input type="number" id="calcLength" placeholder="' + dimPlaceholder() + '" min="0.1" step="any">' +
                    dimUnitSelect('calcLength') +
                '</div>' +
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

    /* ---------- Calculation functions (read unit-converted values) ---------- */

    function calcRoundBar() {
        var d = toMm(parseFloat(document.getElementById('calcDiameter').value));
        var L = toM(parseFloat(document.getElementById('calcLength').value));
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!d || !L) return null;
        var perPc = d * d * 0.006165 * L;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg' };
    }

    function calcFlatBar() {
        var w = toMm(parseFloat(document.getElementById('calcWidth').value));
        var t = toMm(parseFloat(document.getElementById('calcThickness').value));
        var L = toM(parseFloat(document.getElementById('calcLength').value));
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!w || !t || !L) return null;
        var perPc = w * t * L * STEEL_DENSITY / 1000000;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg' };
    }

    function calcSquareBar() {
        var d = toMm(parseFloat(document.getElementById('calcDiameter').value));
        var L = toM(parseFloat(document.getElementById('calcLength').value));
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!d || !L) return null;
        var perPc = d * d * L * STEEL_DENSITY / 1000000;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg' };
    }

    function calcAngleBar() {
        var a = toMm(parseFloat(document.getElementById('calcLegA').value));
        var b = toMm(parseFloat(document.getElementById('calcLegB').value));
        var t = toMm(parseFloat(document.getElementById('calcThickness').value));
        var L = toM(parseFloat(document.getElementById('calcLength').value));
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!a || !b || !t || !L) return null;
        var perPc = (a + b - t) * t * L * STEEL_DENSITY / 1000000;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg' };
    }

    function calcPlate() {
        var t = toMm(parseFloat(document.getElementById('calcThickness').value));
        var w = toMm(parseFloat(document.getElementById('calcWidth').value));
        var L = toMm(parseFloat(document.getElementById('calcLength').value));
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!t || !w || !L) return null;
        var perPc = t * w * L * STEEL_DENSITY / 1000000000;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg' };
    }

    function calcSheet() { return calcPlate(); }

    function calcPipe() {
        var od = toMm(parseFloat(document.getElementById('calcOD').value));
        var t = toMm(parseFloat(document.getElementById('calcThickness').value));
        var L = toM(parseFloat(document.getElementById('calcLength').value));
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!od || !t || !L) return null;
        var perM = (od - t) * t * 0.02466;
        var perPc = perM * L;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg' };
    }

    function calcTube() {
        var w = toMm(parseFloat(document.getElementById('calcWidth').value));
        var h = toMm(parseFloat(document.getElementById('calcHeight').value));
        var t = toMm(parseFloat(document.getElementById('calcThickness').value));
        var L = toM(parseFloat(document.getElementById('calcLength').value));
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!w || !h || !t || !L) return null;
        var perM = (w + h - 2 * t) * 2 * t * 0.0157;
        var perPc = perM * L;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg' };
    }

    function calcBeam() {
        var idx = document.getElementById('calcSize').value;
        var L = toM(parseFloat(document.getElementById('calcLength').value));
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (idx === '' || !L) return null;
        var unitW = BEAM_SIZES[parseInt(idx)].weight;
        var perPc = unitW * L;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg', breakdown: unitW + ' kg/m \u00d7 ' + L.toFixed(2) + ' m' };
    }

    function calcPurlin() {
        var idx = document.getElementById('calcSize').value;
        var L = toM(parseFloat(document.getElementById('calcLength').value));
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (idx === '' || !L) return null;
        var unitW = PURLIN_SIZES[parseInt(idx)].weight;
        var perPc = unitW * L;
        return { perPiece: perPc, total: perPc * q, qty: q, unit: 'kg', breakdown: unitW + ' kg/m \u00d7 ' + L.toFixed(2) + ' m' };
    }

    function calcSheetPile() {
        var idx = document.getElementById('calcSize').value;
        var L = toM(parseFloat(document.getElementById('calcLength').value));
        if (idx === '' || !L) return null;
        var perM = SHEET_PILE_TYPES[parseInt(idx)].weightPerM;
        var total = perM * L;
        return { perPiece: total, total: total, qty: 1, unit: 'kg', breakdown: perM + ' kg/m \u00d7 ' + L.toFixed(2) + ' m' };
    }

    function calcWireMesh() {
        var t = toMm(parseFloat(document.getElementById('calcThickness').value));
        var w = toMm(parseFloat(document.getElementById('calcWidth').value));
        var L = toMm(parseFloat(document.getElementById('calcLength').value));
        var q = parseInt(document.getElementById('calcQty').value) || 1;
        if (!t || !w || !L) return null;
        var areaM2 = (w / 1000) * (L / 1000);
        var perSheet = areaM2 * t * t * 0.00617 * 40;
        return { perPiece: perSheet, total: perSheet * q, qty: q, unit: 'kg', note: 'Approximate weight' };
    }

    function calcRoofing() {
        var t = toMm(parseFloat(document.getElementById('calcThickness').value));
        var w = toMm(parseFloat(document.getElementById('calcWidth').value));
        var L = toMm(parseFloat(document.getElementById('calcLength').value));
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

    /* ---------- Shared: read unit selects after form render ---------- */

    function syncUnitSelects() {
        var dimSels = document.querySelectorAll('.calc-dim-unit');
        var lenSels = document.querySelectorAll('.calc-len-unit');
        dimSels.forEach(function (sel) {
            sel.value = currentDimUnit;
            sel.addEventListener('change', function () {
                currentDimUnit = this.value;
                dimSels.forEach(function (s) { s.value = currentDimUnit; });
            });
        });
        lenSels.forEach(function (sel) {
            sel.value = currentLenUnit;
            sel.addEventListener('change', function () {
                currentLenUnit = this.value;
                lenSels.forEach(function (s) { s.value = currentLenUnit; });
            });
        });
        /* wire per-input unit selects */
        document.querySelectorAll('.calc-unit-select[id$="-unit"]').forEach(function (sel) {
            sel.addEventListener('change', function () {
                if (sel.id.indexOf('calcLength') === 0) {
                    currentLenUnit = sel.value;
                    lenSels.forEach(function (s) { s.value = currentLenUnit; });
                } else {
                    currentDimUnit = sel.value;
                    dimSels.forEach(function (s) { s.value = currentDimUnit; });
                }
            });
        });
    }

    function updateUnitHints() {
        document.querySelectorAll('.calc-dim-unit').forEach(function (sel) { sel.value = currentDimUnit; });
        document.querySelectorAll('.calc-len-unit').forEach(function (sel) { sel.value = currentLenUnit; });
        document.querySelectorAll('.calc-unit-select[id$="-unit"]').forEach(function (sel) {
            if (sel.id.indexOf('calcLength') === 0) {
                sel.value = currentLenUnit;
            } else {
                sel.value = currentDimUnit;
            }
        });
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
        var unitBarContainer = document.getElementById('calcUnitBar');

        var currentType = null;
        var currentCalcFn = null;

        function openCalcModal(productName, categorySlug) {
            var type = resolveType(productName, categorySlug);
            currentType = type;

            if (!type || !TYPE_CONFIG[type]) {
                formContainer.innerHTML = buildUnsupportedForm();
                productNameEl.textContent = productName || '';
                resultEl.classList.remove('show');
                if (unitBarContainer) unitBarContainer.innerHTML = '';
                overlay.classList.add('active');
                document.body.style.overflowY = 'hidden';
                return;
            }

            currentCalcFn = TYPE_CONFIG[type].calc;
            if (unitBarContainer) unitBarContainer.innerHTML = unitToggleHTML(type);
            formContainer.innerHTML = TYPE_CONFIG[type].form();
            productNameEl.textContent = productName || '';
            resultEl.classList.remove('show');
            syncUnitSelects();
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
            if (e.key === 'Escape') {
                if (overlay && overlay.classList.contains('active')) {
                    closeCalcModal();
                } else if (homeOverlay && homeOverlay.classList.contains('active')) {
                    closeHomeCalc();
                }
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
                resultSecondaryEl.textContent = '\u2248 ' + fmt(totalLb) + ' lbs';

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
                formContainer.querySelectorAll('select').forEach(function (sel) { if (sel.className !== 'calc-unit-select') sel.selectedIndex = 0; });
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
           HOMEPAGE WEIGHT CALCULATOR POPUP
           ============================================ */
        var homeOverlay = document.getElementById('homeCalcOverlay');
        var homeFormContainer = document.getElementById('homeCalcFormContainer');
        var homeCalcResult = document.getElementById('homeCalcResult');
        var homeCalcWeightEl = document.getElementById('homeCalcResultWeight');
        var homeCalcSecondaryEl = document.getElementById('homeCalcResultSecondary');
        var homeCalcBreakdownEl = document.getElementById('homeCalcResultBreakdown');
        var homeCalcCalcFn = null;

        function openHomeCalc() {
            if (!homeOverlay) return;
            homeOverlay.classList.add('active');
            document.body.style.overflowY = 'hidden';
        }

        function closeHomeCalc() {
            if (!homeOverlay) return;
            homeOverlay.classList.remove('active');
            document.body.style.overflowY = '';
            if (homeFormContainer) homeFormContainer.innerHTML = '';
            if (homeCalcResult) homeCalcResult.classList.remove('show');
            var sel = document.getElementById('homeCalcProduct');
            if (sel) sel.selectedIndex = 0;
            homeCalcCalcFn = null;
        }

        var homeCalcOpenBtn = document.getElementById('homeCalcOpenBtn');
        if (homeCalcOpenBtn) homeCalcOpenBtn.addEventListener('click', openHomeCalc);

        var homeCalcCloseBtn = document.getElementById('homeCalcClose');
        if (homeCalcCloseBtn) homeCalcCloseBtn.addEventListener('click', closeHomeCalc);

        if (homeOverlay) {
            homeOverlay.addEventListener('click', function (e) {
                if (e.target === homeOverlay) closeHomeCalc();
            });
        }

        var homeCalcSelect = document.getElementById('homeCalcProduct');
        if (homeCalcSelect) {
            homeCalcSelect.addEventListener('change', function () {
                var type = this.value;
                var homeUnitBar = document.getElementById('homeCalcUnitBar');
                if (homeCalcResult) homeCalcResult.classList.remove('show');
                if (!type || !TYPE_CONFIG[type]) {
                    if (homeFormContainer) homeFormContainer.innerHTML = '';
                    if (homeUnitBar) homeUnitBar.innerHTML = '';
                    homeCalcCalcFn = null;
                    return;
                }
                homeCalcCalcFn = TYPE_CONFIG[type].calc;
                if (homeUnitBar) homeUnitBar.innerHTML = unitToggleHTML(type);
                homeFormContainer.innerHTML = TYPE_CONFIG[type].form();
                syncUnitSelects();
            });
        }

        var homeCalcBtnEl = document.getElementById('homeCalcBtn');
        if (homeCalcBtnEl) {
            homeCalcBtnEl.addEventListener('click', function () {
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

        var homeCalcResetEl = document.getElementById('homeCalcResetBtn');
        if (homeCalcResetEl) {
            homeCalcResetEl.addEventListener('click', function () {
                if (homeFormContainer) {
                    homeFormContainer.querySelectorAll('input').forEach(function (i) { i.value = ''; });
                    homeFormContainer.querySelectorAll('select').forEach(function (s) { if (s.className !== 'calc-unit-select') s.selectedIndex = 0; });
                }
                homeCalcResult.classList.remove('show');
            });
        }

        var homeCalcQuoteEl = document.getElementById('homeCalcQuoteBtn');
        if (homeCalcQuoteEl) {
            homeCalcQuoteEl.addEventListener('click', function () {
                closeHomeCalc();
                var quoteTrigger = document.querySelector('.btn-quote-trigger[data-product]');
                if (quoteTrigger) quoteTrigger.click();
            });
        }
    });
})();
