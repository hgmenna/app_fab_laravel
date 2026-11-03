const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const context = { module: { exports: {} } };
vm.runInNewContext(readFileSync(path.join(__dirname, '../../public/js/wordpress-calendar.js'), 'utf8'), context);
const { matches, sortOrder } = context.module.exports;
const filters = { estado: 'todos', buscar: '', desde: '', hasta: '' };
const rows = [
    { id: 1, torneo: 'Torneo Córdoba', club: 'Club San Martín', estado_torneo: 'Finalizado', fecha_inicio: '2026-10-31', fecha_fin: '2026-11-01' },
    { id: 2, torneo: 'Copa Primavera', club: 'Club San Martín', estado_torneo: 'Próximo', fecha_inicio: '2026-11-06', fecha_fin: '2026-11-08' },
    { id: 3, torneo: 'Torneo anterior', club: 'Club Sur', estado_torneo: 'Finalizado', fecha_inicio: '2025-12-30', fecha_fin: '2026-01-02' },
    { id: 4, torneo: 'Copa actual', club: 'Club Sur', estado_torneo: 'En curso', fecha_inicio: '2026-11-01', fecha_fin: '2026-11-03' },
];
const select = values => rows.filter(row => matches(row, { ...filters, ...values })).map(row => row.id);

test('state filters exclude other states, including unknown records', () => {
    assert.deepEqual(select({ estado: 'finalizados' }), [1, 3]);
    assert.deepEqual(select({ estado: 'proximos' }), [2]);
    assert.deepEqual(select({ estado: 'encurso' }), [4]);
    assert.equal(matches(undefined, filters), false);
});
test('search combines words across venue and tournament, ignoring accents and whitespace', () => {
    assert.deepEqual(select({ buscar: '  CORDOBA   martin ' }), [1]);
    assert.deepEqual(select({ buscar: 'cordoba', estado: 'proximos' }), []);
    assert.deepEqual(select({ buscar: 'inexistente' }), []);
});
test('date range includes overlapping tournaments across months and inclusive boundaries', () => {
    assert.deepEqual(select({ desde: '2026-11-01', hasta: '2026-11-01' }), [1, 4]);
    assert.deepEqual(select({ hasta: '2026-10-31', estado: 'finalizados' }), [1, 3]);
    assert.deepEqual(select({ desde: '2026-11-08', hasta: '2026-11-06' }), []);
});
test('finished tournaments sort newest first across years and use a stable tie breaker', () => {
    const names = ['id', 'fecha_inicio', 'fecha_fin'];
    const order = sortOrder('finalizados', name => names.indexOf(name));
    assert.equal(JSON.stringify(order), JSON.stringify([[2, 'desc'], [0, 'desc']]));
    const sorted = rows.filter(row => matches(row, { ...filters, estado: 'finalizados' }))
        .sort((a, b) => {
            for (const [index, direction] of order) {
                const comparison = String(a[names[index]]).localeCompare(String(b[names[index]]));
                if (comparison) return comparison * (direction === 'desc' ? -1 : 1);
            }
            return 0;
        });
    assert.deepEqual(sorted.map(row => row.id), [1, 3]);
    assert.equal(JSON.stringify(sortOrder('proximos', name => names.indexOf(name))), JSON.stringify([[1, 'asc'], [0, 'asc']]));
});
