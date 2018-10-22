import React, {Component} from 'react';
import PropTypes from 'prop-types';

class ExportModalSendEmail extends Component {

    static propTypes = {
        sendEmailHandler: PropTypes.func.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {
            cloudCheckBox: true,
            email: ''
        };
    }

    render() {
        return (
            <div id="sender">
                <h1> Download your TMX </h1>
                <h3> A copy we’ll be shared in public cloud </h3>

                <div className="sender-content">
                    <input type="text" tabIndex="0" placeholder="insert an email"
                           value={this.state.email}
                           onChange={this.inputHandler}/>
                    <p> We’ll send you an email when the file is ready </p>
                </div>

                <div className="selection">
                    <div className="ui toggle checkbox">
                        <input
                            type="checkbox" name="cloud"
                            tabIndex="5"
                            checked={this.state.cloudCheckBox}
                            value={this.state.cloudCheckBox}
                            onChange={this.cloudHandler} />
                        <label className={ this.state.cloudCheckBox ? 'active' : 'inactive'}>Help to improve the public cloud</label>
                    </div>
                </div>
                <div className="actions">
                    <button className="send-btn ui button" tabIndex="3" type="button">
                        Send
                    </button>
                    <a href="javascript:void(0);" onClick={this.props.sendEmailHandler}> &lt; Go to cloud import</a>
                </div>


            </div>
        );
    }

    inputHandler = (e) =>{
        this.setState({
            email: e.target.value
        })
    }
    cloudHandler = () => {
        this.setState({
            cloudCheckBox: !this.state.cloudCheckBox,
        });
    };

}
export default ExportModalSendEmail;
