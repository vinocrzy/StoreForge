import React, { useState } from 'react';
import { Form, Input, Button, Card, message, Typography, Space } from 'antd';
import { UserOutlined, LockOutlined } from '@ant-design/icons';
import { useNavigate } from 'react-router-dom';
import { useLoginMutation } from '../services/auth';
import { useAppDispatch } from '../store/hooks';
import { setCredentials } from '../store/authSlice';
import type { LoginRequest } from '../types/auth';

const { Title, Text } = Typography;

const LoginPage: React.FC = () => {
  const [form] = Form.useForm();
  const [login, { isLoading }] = useLoginMutation();
  const dispatch = useAppDispatch();
  const navigate = useNavigate();

  const onFinish = async (values: LoginRequest) => {
    try {
      const response = await login(values).unwrap();
      
      // Save credentials to Redux store
      dispatch(setCredentials({
        user: response.user,
        token: response.token,
        store: response.store,
      }));

      message.success('Login successful!');
      navigate('/');
    } catch (error: any) {
      const errorMessage = error?.data?.message || 'Login failed. Please check your credentials.';
      message.error(errorMessage);
    }
  };

  return (
    <div style={{
      display: 'flex',
      justifyContent: 'center',
      alignItems: 'center',
      minHeight: '100vh',
      background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    }}>
      <Card
        style={{ width: 400, boxShadow: '0 4px 12px rgba(0,0,0,0.15)' }}
      >
        <Space direction="vertical" size="large" style={{ width: '100%' }}>
          <div style={{ textAlign: 'center' }}>
            <Title level={2} style={{ marginBottom: 8 }}>
              E-Commerce Admin
            </Title>
            <Text type="secondary">
              Sign in to manage your store
            </Text>
          </div>

          <Form
            form={form}
            name="login"
            onFinish={onFinish}
            autoComplete="off"
            layout="vertical"
            size="large"
          >
            <Form.Item
              name="login"
              rules={[
                { required: true, message: 'Please input your phone or email!' },
              ]}
            >
              <Input
                prefix={<UserOutlined />}
                placeholder="Phone (+1234567890) or Email"
                autoComplete="username"
              />
            </Form.Item>

            <Form.Item
              name="password"
              rules={[
                { required: true, message: 'Please input your password!' },
              ]}
            >
              <Input.Password
                prefix={<LockOutlined />}
                placeholder="Password"
                autoComplete="current-password"
              />
            </Form.Item>

            <Form.Item>
              <Button
                type="primary"
                htmlType="submit"
                loading={isLoading}
                block
              >
                Sign In
              </Button>
            </Form.Item>
          </Form>

          <div style={{ textAlign: 'center' }}>
            <Text type="secondary" style={{ fontSize: 12 }}>
              Default credentials: admin@ecommerce-platform.com / password
            </Text>
          </div>
        </Space>
      </Card>
    </div>
  );
};

export default LoginPage;
