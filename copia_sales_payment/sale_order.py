# -*- coding: utf-8 -*-
#################################################################################
#
#    OpenERP, Open Source Management Solution
#    Copyright (C) 2013 Outside The Box Africa Ltd <info@otbafrica.com>
#
#    This program is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.
#
#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
#################################################################################

from openerp.osv import fields, orm
from openerp.osv import fields, osv
from openerp.tools.translate import _
import openerp.addons.decimal_precision as dp
from openerp.tools import float_compare
from datetime import datetime, timedelta
from datetime import time
from datetime import date
import pdb

class sale_order(orm.Model):
    _name = "sale.order"
    _inherit = "sale.order"

    _columns = {
        'sale_order_payment_number': fields.one2many('sale.order.payment.number', 'sale_id', 'Payment Numbers'),
    }

    def vendor_payment_array(self, cr, uid, context=None):
        
        """ To get all the vendors outstanding amount
        """
        #previous_day = self.get_copia_previous_day(cr, uid,context)
        #orders_payments_dict = self.get_total_payments_and_order_amounts_previous(cr, uid, vendor_id, previous_day)

        route_vendor_dict = {}
        partner_category_obj = self.pool.get('res.partner.category')
        partner_category_ids = partner_category_obj.search(cr, uid, [('name', '=', 'Vendor')])
        vendor_obj = self.pool.get('res.partner')
        vendor_list = vendor_obj.search(cr, uid, [('category_id', '=', partner_category_ids[0])])
        route_list = self.pool.get('delivery.routing').search(cr, uid, [('active', '=', True)])
        if route_list:
            for route_id in route_list: 
                route_name = self.pool.get('delivery.routing').browse(cr,uid,route_id).name
                agents = self.pool.get('delivery.routing').browse(cr,uid,route_id).agents
                if agents:
                    #pdb.set_trace()
                    vendor_route_list =[]
                    vendor_dic ={}
                    for vendor_id in agents:
                
                        balance = self.get_copia_outstanding_balance(cr, uid, [vendor_id.id]) + self.get_previous_day_balance(cr, uid, [vendor_id.id])
                
                        customer = vendor_id.name
                        vendor_dic.update({ customer : balance })
                    li = vendor_dic.items()   

                    route_vendor_dict.update({route_name : li })



	    
        return route_vendor_dict


    def get_copia_outstanding_balance(self, cr, uid, vendor_id, context=None):
        """ Gets balance for vendor for yesterday's sales orders.
        @return: List of values
        """
        
        previous_day = self.get_copia_previous_day(cr, uid,context)
        orders_payments_dict = self.get_total_payments_and_order_amounts_today(cr, uid, vendor_id, previous_day)
        return orders_payments_dict['order_amounts'] - orders_payments_dict['payments']



class sale_order_payment_number(osv.osv):
    _name = "sale.order.payment.number"

    _columns = {
        'name': fields.char('Payment Number', size =200, readonly=True),
        'amount': fields.float('Paid Amount', readonly=True),
        'date': fields.datetime('Date', readonly=True),
        'sale_id': fields.many2one('sale.order', 'Sales Order', required=True, ondelete='cascade', select=True, readonly=True),
    }

# vim:expandtab:smartindent:tabstop=4:softtabstop=4:shiftwidth=4:
