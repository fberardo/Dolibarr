-- ===================================================================
-- Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================

ALTER TABLE llx_supplier_account_movement
ADD INDEX idx_supplier_account_movement_dateo(dateo);

ALTER TABLE llx_supplier_account_movement
ADD INDEX idx_supplier_account_movement_fk_supplier_account(fk_supplier_account);

ALTER TABLE llx_supplier_account_movement
ADD CONSTRAINT fk_supplier_account_movement_fk_supplier_account
FOREIGN KEY (fk_supplier_account)
REFERENCES llx_supplier_account (rowid);

ALTER TABLE llx_supplier_account_movement
ADD CONSTRAINT fk_supplier_account_movement_fk_cheque
FOREIGN KEY (fk_cheque)
REFERENCES llx_cheque (rowid);

ALTER TABLE llx_supplier_account_movement
ADD CONSTRAINT fk_supplier_account_movement_fk_account_id
FOREIGN KEY (fk_account_id)
REFERENCES llx_bank_account (rowid);

ALTER TABLE llx_supplier_account_movement
ADD CONSTRAINT fk_supplier_account_movement_acc_line_id
FOREIGN KEY (acc_line_id)
REFERENCES llx_bank (rowid);
