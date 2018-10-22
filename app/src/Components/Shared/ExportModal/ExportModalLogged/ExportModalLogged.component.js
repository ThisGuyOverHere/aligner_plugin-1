import React, {Component} from 'react';
import {getUserInitials} from "../../../../Helpers/SystemUtils.helper";
import PropTypes from "prop-types";
import {httpGetTmxList, httpCreateTmx, httpSaveTmx} from "../../../../HttpRequests/Tmx.http";

class ExportModalLogged extends Component {

    static propTypes = {
        user: PropTypes.oneOfType([PropTypes.bool, PropTypes.object]),
    };

    constructor(props) {
        super(props);
        this.state = {
            cloudCheckBox: true,
            tmxList: [],
            selected: null,
            newTmx: null,
        };
    }

    componentDidMount = () => {
        this.fetchTmx();
    };

    fetchTmx = async () => {
        try {
            const res = await fetch('/plugins/aligner/tm/mine');
            const list = await res.json();
            this.setState({
                tmxList: list,
                selected: list[0]
            });
        } catch (e) {
            console.error(e)
        }
        return true;
    };

    componentWillUnmount = () => {

    };

    render() {
        return (
            <div id="logged">
                <h1>Export TMX in private or public cloud</h1>
                <h3>No empty segment or hided row will be exported</h3>
                <div className="user-data">
                    <div className="ui logged label">
                        {getUserInitials(this.props.user.first_name, this.props.user.last_name)}
                    </div>
                    <div className="info">
                        <h3> {this.props.user.first_name} </h3>
                        <p>  {this.props.user.email} </p>
                    </div>
                </div>

                {this.state.tmxList.length > 0 ?
                    <div>
                        <div className="memories">
                            {this.renderMemories()}
                        </div>
                        <div className="line"></div>
                    </div>
                    : null}

                {this.state.newTmx ?
                    <div className="new-memory">
                        <form onSubmit={this.saveMemory}>
                            <input type="text" tabIndex="4" onChange={this.handleInput} placeholder="Description of tmx"/>
                            <button type="submit" className="ui button">Save</button>
                        </form>
                        <p>{this.state.newTmx.key}</p>
                    </div>
                    : <button className="ui button" onClick={this.createMemory}>Create new resource</button>}


                <div className="selection">
                    <div className="ui checked toggle checkbox">
                        <input
                            type="checkbox" name="cloud"
                            tabIndex="5"
                            checked={this.state.cloudCheckBox}
                            value={this.state.cloudCheckBox}
                            onChange={this.cloudHandler} />
                        <label className={this.state.cloudCheckBox ? 'active' : 'inactive'}>Help to improve the public
                            cloud</label>
                    </div>
                </div>

                <button className="export-btn ui button" tabIndex="6" type="">
                    Export
                </button>
            </div>
        );
    }

    renderMemories = () => {
        let memories = [];
        this.state.tmxList.map((element, index) => {
            const memory = <div className="memory" key={index}>
                <div className="radio-container">
                    <input type="radio" className="hidden" name="memory"
                           checked={element.key === this.state.selected.key}
                           onChange={this.handleCheckRadio}
                           value={index} tabIndex={index}/>
                </div>
                <div className="memory-info">
                    <p>{element.name? element.name : 'Private TM and Glossary'}</p>
                    <p>{element.key}</p>
                </div>
            </div>;
            memories.push(memory);
            return element;
        });

        return memories;
    };
    handleCheckRadio = (e) =>{
        this.setState({
           selected: this.state.tmxList[e.target.value]
        });
    };
    createMemory = () => {
        httpCreateTmx().then((response) => {
            const tmx = {
                id: response.data.id,
                key: response.data.key,
                pass: response.data.pass,
                name: null
            };
            this.setState({
                newTmx: tmx
            });
        }, (error) => {

        })
    };
    saveMemory = (e) => {
        e.preventDefault();
        httpSaveTmx(this.state.newTmx.key,this.state.newTmx.name).then((response)=>{
            if(response.data){
                let list = this.state.tmxList;
                list.unshift(this.state.newTmx);
                this.setState({
                    tmxList: list,
                    newTmx: null
                });
            }
        }, (error)=>{
           console.error(error)
        });

    };
    handleInput = (e) =>{
        let newTmx = this.state.newTmx;
        newTmx.name = e.target.value;
        this.setState({
            newTmx: newTmx
        });
    };

    cloudHandler = () => {
        this.setState({
            cloudCheckBox: !this.state.cloudCheckBox,
        })
    };

}

export default ExportModalLogged;
