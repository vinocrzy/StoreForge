import React from 'react';
import { Typography, Row, Col, Card, Statistic } from 'antd';
import {
  ShoppingOutlined,
  UserOutlined,
  DollarOutlined,
  InboxOutlined,
} from '@ant-design/icons';

const { Title } = Typography;

const DashboardPage: React.FC = () => {
  return (
    <div>
      <Title level={2}>Dashboard</Title>
      
      <Row gutter={[16, 16]} style={{ marginTop: 24 }}>
        <Col xs={24} sm={12} lg={6}>
          <Card>
            <Statistic
              title="Total Products"
              value={90}
              prefix={<ShoppingOutlined />}
              valueStyle={{ color: '#3f8600' }}
            />
          </Card>
        </Col>
        <Col xs={24} sm={12} lg={6}>
          <Card>
            <Statistic
              title="Total Orders"
              value={45}
              prefix={<InboxOutlined />}
              valueStyle={{ color: '#1890ff' }}
            />
          </Card>
        </Col>
        <Col xs={24} sm={12} lg={6}>
          <Card>
            <Statistic
              title="Total Customers"
              value={45}
              prefix={<UserOutlined />}
              valueStyle={{ color: '#722ed1' }}
            />
          </Card>
        </Col>
        <Col xs={24} sm={12} lg={6}>
          <Card>
            <Statistic
              title="Total Revenue"
              value={12500.50}
              prefix={<DollarOutlined />}
              precision={2}
              valueStyle={{ color: '#cf1322' }}
            />
          </Card>
        </Col>
      </Row>
      
      <Card title="Welcome to E-Commerce Admin Panel" style={{ marginTop: 24 }}>
        <p>
          This is the main dashboard where you can manage your e-commerce platform.
        </p>
        <p>
          Use the sidebar navigation to access different sections:
        </p>
        <ul>
          <li><strong>Products:</strong> Manage your product catalog, categories, and inventory</li>
          <li><strong>Orders:</strong> View and manage customer orders, payments, and fulfillment</li>
          <li><strong>Customers:</strong> Manage customer accounts and addresses</li>
          <li><strong>Inventory:</strong> Track stock levels across warehouses</li>
          <li><strong>Settings:</strong> Configure store settings and preferences</li>
        </ul>
      </Card>
    </div>
  );
};

export default DashboardPage;
